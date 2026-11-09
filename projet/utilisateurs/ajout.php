<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$erreurs = [];
$rolesDisponibles = db()->query('SELECT libelle_role FROM role ORDER BY libelle_role')->fetchAll(PDO::FETCH_COLUMN);
$donnees = [
    'login'  => '',
    'mail'   => '',
    'prenom' => '',
    'nom'    => '',
    'role'   => 'client',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        $donnees = [
            'login'  => trim((string) ($_POST['login'] ?? '')),
            'mail'   => mb_strtolower(trim((string) ($_POST['mail'] ?? ''))),
            'prenom' => trim((string) ($_POST['prenom'] ?? '')),
            'nom'    => trim((string) ($_POST['nom'] ?? '')),
            'role'   => (string) ($_POST['role'] ?? 'client'),
        ];
        $mdp = (string) ($_POST['mdp'] ?? '');
        $mdp2 = (string) ($_POST['mdp2'] ?? '');

        $erreurs = valider_inscription(
            $donnees['login'],
            $donnees['mail'],
            $mdp,
            $mdp2,
            $donnees['prenom'],
            $donnees['nom']
        );

        if (!in_array($donnees['role'], $rolesDisponibles, true)) {
            $erreurs[] = 'Rôle invalide.';
        }

        if ($erreurs === []) {
            $res = creer_utilisateur(
                $donnees['login'],
                $donnees['mail'],
                $mdp,
                $donnees['prenom'],
                $donnees['nom'],
                $donnees['role']
            );

            if (is_string($res)) {
                $erreurs[] = $res;
            } else {
                flash_set('Utilisateur ajouté avec succès.');
                rediriger('/utilisateurs/liste.php');
            }
        }
    }
}

$titre = 'Ajout d\'un utilisateur';
require __DIR__ . '/../templates/header.php';
?>

<form method="post" autocomplete="off">
  <?= csrf_champ() ?>

  <label>Prénom
    <input type="text" name="prenom" value="<?= h($donnees['prenom']) ?>" required maxlength="100"
           pattern="[A-Za-zÀ-ÿ '-]{1,100}" autocomplete="given-name">
  </label>

  <label>Nom
    <input type="text" name="nom" value="<?= h($donnees['nom']) ?>" required maxlength="100"
           pattern="[A-Za-zÀ-ÿ '-]{1,100}" autocomplete="family-name">
  </label>

  <label>Identifiant
    <input type="text" name="login" value="<?= h($donnees['login']) ?>" required maxlength="30"
           pattern="[A-Za-z0-9_.-]{3,30}" autocomplete="username">
  </label>

  <label>E-mail
    <input type="email" name="mail" value="<?= h($donnees['mail']) ?>" required maxlength="254" autocomplete="email">
  </label>

  <label>Mot de passe (12 caractères min., 1 maj., 1 min., 1 chiffre)
    <input type="password" name="mdp" required minlength="12" maxlength="200"
           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,200}" autocomplete="new-password">
  </label>

  <label>Confirmer le mot de passe
    <input type="password" name="mdp2" required minlength="12" maxlength="200"
           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,200}" autocomplete="new-password">
  </label>

  <label>Rôle
    <select name="role" required>
      <?php foreach ($rolesDisponibles as $role): ?>
        <option value="<?= h($role) ?>" <?= $role === $donnees['role'] ? 'selected' : '' ?>>
          <?= h(ucfirst($role)) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <button type="submit">Ajouter l'utilisateur</button>
</form>

<p><a href="liste.php">Retour à la gestion des utilisateurs</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
