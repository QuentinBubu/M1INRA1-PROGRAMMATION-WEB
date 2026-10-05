<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
exiger_visiteur();

$erreurs = [];
$login = '';
$mail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'Requête invalide, veuillez réessayer.';
    } else {
        $login = trim((string)($_POST['login'] ?? ''));
        $mail  = mb_strtolower(trim((string)($_POST['mail'] ?? '')));
        $mdp   = (string)($_POST['mdp'] ?? '');
        $mdp2  = (string)($_POST['mdp2'] ?? '');

        $erreurs = valider_inscription($login, $mail, $mdp, $mdp2);

        if (!$erreurs) {
            $res = creer_utilisateur($login, $mail, $mdp);
            if (is_string($res)) {
                $erreurs[] = $res;
            } else {
                flash_set('Compte créé, vous pouvez vous connecter.');
                rediriger('connexion.php');
            }
        }
    }
}

$titre = 'Inscription';
require __DIR__ . '/templates/header.php';
?>
<form method="post" autocomplete="off" novalidate>
  <?= csrf_champ() ?>
  <label>Identifiant
    <input type="text" name="login" value="<?= h($login) ?>" required maxlength="30" autocomplete="username">
  </label>
  <label>E-mail
    <input type="email" name="mail" value="<?= h($mail) ?>" required maxlength="254" autocomplete="email">
  </label>
  <label>Mot de passe (12 caractères min.)
    <input type="password" name="mdp" required minlength="12" maxlength="200" autocomplete="new-password">
  </label>
  <label>Confirmer le mot de passe
    <input type="password" name="mdp2" required minlength="12" maxlength="200" autocomplete="new-password">
  </label>
  <button type="submit">Créer mon compte</button>
</form>
<p class="lien">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
<?php require __DIR__ . '/templates/footer.php'; ?>
