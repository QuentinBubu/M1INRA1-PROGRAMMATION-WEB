<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
exiger_visiteur();

$erreurs = [];
$login  = '';
$mail   = '';
$prenom = '';
$nom    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'Requête invalide, veuillez réessayer.';
    } else {
        $login  = trim((string) ($_POST['login'] ?? ''));
        $mail   = mb_strtolower(trim((string) ($_POST['mail'] ?? '')));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $nom    = trim((string) ($_POST['nom'] ?? ''));
        $mdp    = (string) ($_POST['mdp'] ?? '');
        $mdp2   = (string) ($_POST['mdp2'] ?? '');

        $erreurs = valider_inscription($login, $mail, $mdp, $mdp2, $prenom, $nom);

        if (!$erreurs) {
            $res = creer_utilisateur($login, $mail, $mdp, $prenom, $nom);
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
<form method="post" autocomplete="off">
  <?= csrf_champ() ?>
  <label>Prénom
    <input type="text" name="prenom" value="<?= h($prenom) ?>" required maxlength="100"
           pattern="[A-Za-zÀ-ÿ '-]{1,100}" autocomplete="given-name">
  </label>
  <label>Nom
    <input type="text" name="nom" value="<?= h($nom) ?>" required maxlength="100"
           pattern="[A-Za-zÀ-ÿ '-]{1,100}" autocomplete="family-name">
  </label>
  <label>Identifiant
    <input type="text" name="login" value="<?= h($login) ?>" required maxlength="30"
           pattern="[A-Za-z0-9_.-]{3,30}" autocomplete="username">
  </label>
  <label>E-mail
    <input type="email" name="mail" value="<?= h($mail) ?>" required maxlength="254" autocomplete="email">
  </label>
  <label>Mot de passe (12 caractères min., 1 maj., 1 min., 1 chiffre)
    <input type="password" name="mdp" required minlength="12" maxlength="200"
           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,200}" autocomplete="new-password">
  </label>
  <label>Confirmer le mot de passe
    <input type="password" name="mdp2" required minlength="12" maxlength="200"
           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,200}" autocomplete="new-password">
  </label>
  <button type="submit">Créer mon compte</button>
</form>
<p class="lien">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
<?php require __DIR__ . '/templates/footer.php'; ?>
