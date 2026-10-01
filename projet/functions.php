<?php
declare(strict_types=1);

/* ========== Utilitaires ========== */
/**
 * Protection XSS
 *
 * @param string|null $s
 * @return string Chaîne encodée
 */
function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirection de l'utilisateur
 *
 * @param string $url
 * @return never
 */
function rediriger(string $url): never
{
    die(header('Location: ' . $url));
}

/**
 * Enregistre un message flash en session.
 *
 * @param string $msg Message a afficher une seule fois.
 * @return void
 */
function flash_set(string $msg): void
{
    $_SESSION['flash'] = $msg;
}

/**
 * Recupere puis supprime le message flash de la session.
 *
 * @return string|null Message flash, ou null s'il n'y en a pas.
 */
function flash_get(): ?string
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/* ========== CSRF ========== */
/**
 * Recupere le jeton CSRF courant ou en genere un nouveau.
 *
 * @return string Jeton CSRF.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Genere le champ HTML contenant le jeton CSRF.
 *
 * @return string Champ HTML masque.
 */
function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

/**
 * Verifie le jeton CSRF transmis dans la requete POST.
 *
 * @return bool true si le jeton est valide, sinon false.
 */
function csrf_valide(): bool
{
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/* ========== Etat de connexion ========== */
/**
 * Indique si un utilisateur est actuellement connecte.
 *
 * @return bool true si un utilisateur est connecte, sinon false.
 */
function est_connecte(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Redirige vers la connexion si aucun utilisateur n'est connecte.
 *
 * @return void
 */
function exiger_connexion(): void
{
    if (!est_connecte()) {
        rediriger('connexion.php');
    }
}

/**
 * Redirige vers le profil si un utilisateur est deja connecte.
 *
 * @return void
 */
function exiger_visiteur(): void
{
    if (est_connecte()) {
        rediriger('profil.php');
    }
}

/**
 * Initialise les donnees de session a partir d'un utilisateur.
 *
 * @param array $u Donnees de l'utilisateur authentifie.
 * @return void
 */
function ouvrir_session(array $u): void
{
    session_regenerate_id(true);   // anti fixation de session
    $_SESSION['user_id']       = (int)$u['user_id'];
    $_SESSION['user_login']    = $u['user_login'];
    $_SESSION['user_compte_id'] = (int)$u['user_compte_id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf']          = bin2hex(random_bytes(32));
}

/**
 * Supprime les donnees de session et detruit la session courante.
 *
 * @return void
 */
function deconnecter(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/* ========== Validation ========== */
/**
 * Valide les donnees saisies lors d'une inscription.
 *
 * @param string $login Identifiant souhaite.
 * @param string $mail Adresse e-mail saisie.
 * @param string $mdp Mot de passe saisi.
 * @param string $mdp2 Confirmation du mot de passe.
 * @return array<int, string> Liste des erreurs de validation.
 */
function valider_inscription(string $login, string $mail, string $mdp, string $mdp2): array
{
    $e = [];
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $login)) {
        $e[] = 'Identifiant : 3 à 30 caractères (lettres, chiffres, _ . -).';
    }
    if (strlen($mail) > 254 || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $e[] = 'Adresse e-mail invalide.';
    }
    if (mb_strlen($mdp) < 12) {
        $e[] = 'Le mot de passe doit contenir au moins 12 caractères.';
    } elseif (mb_strlen($mdp) > 200) {
        $e[] = 'Mot de passe trop long (200 caractères maximum).';
    } elseif (!preg_match('/[a-z]/', $mdp) || !preg_match('/[A-Z]/', $mdp) || !preg_match('/\d/', $mdp)) {
        $e[] = 'Le mot de passe doit contenir une minuscule, une majuscule et un chiffre.';
    }
    if ($mdp !== $mdp2) {
        $e[] = 'Les mots de passe ne correspondent pas.';
    }
    return $e;
}

/* ========== Création d'utilisateur ========== */
/**
 * Retourne l'id créé, ou une chaîne d'erreur (string) si login/mail déjà pris.
 * user_compte_id : ici attribué automatiquement (MAX+1).
 */
function creer_utilisateur(string $login, string $mail, string $mdp): int|string
{
    $pdo  = db();
    $hash = password_hash($mdp, PASSWORD_DEFAULT);

    for ($essai = 0; $essai < 5; $essai++) {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare('SELECT 1 FROM `user` WHERE user_login = :l OR user_mail = :m LIMIT 1');
            $st->execute(['l' => $login, 'm' => $mail]);
            if ($st->fetchColumn()) {
                $pdo->rollBack();
                return 'Identifiant ou e-mail déjà utilisé.';
            }

            $compte = (int)$pdo->query('SELECT COALESCE(MAX(user_compte_id), 0) + 1 FROM `user` FOR UPDATE')->fetchColumn();

            $ins = $pdo->prepare(
                'INSERT INTO `user` (user_login, user_password, user_compte_id, user_mail)
                 VALUES (:l, :p, :c, :m)'
            );
            $ins->execute(['l' => $login, 'p' => $hash, 'c' => $compte, 'm' => $mail]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() !== '23000') { // autre chose qu'un doublon => vraie erreur
                throw $e;
            }
            // doublon (concurrence sur compte_id ou login/mail) : on retente
        }
    }
    return 'Inscription impossible pour le moment, réessayez.';
}
