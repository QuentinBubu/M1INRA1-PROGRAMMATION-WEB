<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
exiger_visiteur();

$erreurs = [];
$identifiant = '';
$messageGenerique = 'Identifiant ou mot de passe incorrect.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim((string)($_POST['identifiant'] ?? ''));
    $mdp         = (string)($_POST['mdp'] ?? '');

    if (!csrf_valide()) {
        $erreurs[] = 'Requête invalide, veuillez réessayer.';
    } elseif ($identifiant === '' || $mdp === '' || strlen($identifiant) > 254 || strlen($mdp) > 200) {
        $erreurs[] = $messageGenerique;
    } else {
        // Le login n'accepte pas "@" : pas d'ambiguité entre login et e-mail
        $st = db()->prepare(
            'SELECT user_id, user_login, user_password, user_compte_id
             FROM `user` WHERE user_login = :a OR user_mail = :b LIMIT 1'
        );
        $st->execute(['a' => $identifiant, 'b' => strtolower($identifiant)]);
        $u = $st->fetch();

        if ($u) {
            $ok = password_verify($mdp, $u['user_password']);
        } else {
            password_hash($mdp, PASSWORD_ARGON2ID);
            $ok = false;
        }

        if ($ok) {
            if (password_needs_rehash($u['user_password'], PASSWORD_ARGON2ID)) {
                $up = db()->prepare('UPDATE `user` SET user_password = :p WHERE user_id = :id');
                $up->execute(['p' => password_hash($mdp, PASSWORD_ARGON2ID), 'id' => $u['user_id']]);
            }

            ouvrir_session($u);
            rediriger('profil.php');
        }

        $erreurs[] = $messageGenerique;
    }
}

$titre = 'Connexion';
require __DIR__ . '/header.php';
?>
<form method="post" novalidate>
  <?= csrf_champ() ?>
  <label>Identifiant ou e-mail
    <input type="text" name="identifiant" value="<?= h($identifiant) ?>" required maxlength="254" autocomplete="username">
  </label>
  <label>Mot de passe
    <input type="password" name="mdp" required maxlength="200" autocomplete="current-password">
  </label>
  <button type="submit">Se connecter</button>
</form>
<p class="lien">Pas de compte ? <a href="inscription.php">S'inscrire</a></p>
<?php require __DIR__ . '/footer.php'; ?>
