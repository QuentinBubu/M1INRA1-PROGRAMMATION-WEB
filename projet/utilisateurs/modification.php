<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    flash_set('Utilisateur introuvable.');
    rediriger('/utilisateurs/liste.php');
}

$st = db()->prepare(
    'SELECT u.user_id, u.user_login, u.user_mail,
            c.prenom_c, c.nom_c
     FROM `user` u
     LEFT JOIN compte c ON c.user_id = u.user_id
     WHERE u.user_id = :id
     LIMIT 1'
);
$st->execute(['id' => $id]);
$utilisateur = $st->fetch();

if ($utilisateur === false) {
    flash_set('Utilisateur introuvable.');
    rediriger('/utilisateurs/liste.php');
}

$rolesDisponibles = db()->query('SELECT libelle_role FROM role ORDER BY libelle_role')->fetchAll(PDO::FETCH_COLUMN);
$rolesCible = roles_utilisateur($id);
$roleActuel = $rolesCible[0] ?? 'client';
$estCourant = $id === (int) $_SESSION['user_id'];

$erreurs = [];
$donnees = [
    'login'  => $utilisateur['user_login'],
    'mail'   => $utilisateur['user_mail'],
    'prenom' => $utilisateur['prenom_c'] ?? '',
    'nom'    => $utilisateur['nom_c'] ?? '',
    'role'   => $roleActuel,
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

        $erreurs = valider_infos_utilisateur(
            $donnees['login'],
            $donnees['mail'],
            $donnees['prenom'],
            $donnees['nom']
        );

        if (!in_array($donnees['role'], $rolesDisponibles, true)) {
            $erreurs[] = 'Rôle invalide.';
        }

        $cibleEstAdmin = in_array('admin', $rolesCible, true);
        if ($cibleEstAdmin && $donnees['role'] !== 'admin') {
            if ($estCourant) {
                $erreurs[] = 'Vous ne pouvez pas retirer votre propre rôle administrateur.';
            } elseif (compter_admins() <= 1) {
                $erreurs[] = 'Impossible de retirer le rôle du dernier administrateur.';
            }
        }

        if ($erreurs === []) {
            $st = db()->prepare(
                'SELECT 1 FROM `user`
                 WHERE (user_login = :login OR user_mail = :mail)
                   AND user_id <> :id
                 LIMIT 1'
            );
            $st->execute([
                'login' => $donnees['login'],
                'mail'  => $donnees['mail'],
                'id'    => $id,
            ]);
            if ($st->fetchColumn()) {
                $erreurs[] = 'Identifiant ou e-mail déjà utilisé.';
            }
        }

        if ($erreurs === []) {
            $pdo = db();
            try {
                $pdo->beginTransaction();

                $st = $pdo->prepare('UPDATE `user` SET user_login = :login, user_mail = :mail WHERE user_id = :id');
                $st->execute([
                    'login' => $donnees['login'],
                    'mail'  => $donnees['mail'],
                    'id'    => $id,
                ]);

                $st = $pdo->prepare(
                    'INSERT INTO compte (user_id, prenom_c, nom_c)
                     VALUES (:id, :prenom, :nom)
                     ON DUPLICATE KEY UPDATE prenom_c = VALUES(prenom_c), nom_c = VALUES(nom_c)'
                );
                $st->execute([
                    'id'     => $id,
                    'prenom' => $donnees['prenom'],
                    'nom'    => $donnees['nom'],
                ]);

                $roleStmt = $pdo->prepare('SELECT id_role FROM role WHERE libelle_role = :role LIMIT 1');
                $roleStmt->execute(['role' => $donnees['role']]);
                $idRole = $roleStmt->fetchColumn();
                if ($idRole === false) {
                    throw new RuntimeException('Rôle absent de la base de données.');
                }

                $pdo->prepare('DELETE FROM droit WHERE user_id = :id')->execute(['id' => $id]);
                $pdo->prepare('INSERT INTO droit (user_id, id_role) VALUES (:id, :role)')
                    ->execute(['id' => $id, 'role' => (int) $idRole]);

                $pdo->commit();

                if ($estCourant) {
                    $_SESSION['user_login'] = $donnees['login'];
                    $_SESSION['roles'] = roles_utilisateur($id);
                }

                flash_set('Utilisateur modifié avec succès.');
                rediriger('/utilisateurs/liste.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Modification utilisateur : ' . $e->getMessage());
                $erreurs[] = 'Impossible de modifier l\'utilisateur.';
            }
        }
    }
}

$titre = 'Modification d\'un utilisateur';
require __DIR__ . '/../templates/header.php';
?>

<p><strong>Utilisateur :</strong> <?= h($utilisateur['user_login']) ?> (ID <?= h((string) $id) ?>)</p>

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

  <?php if ($estCourant): ?>
    <input type="hidden" name="role" value="admin">
    <p>Rôle : <strong>admin</strong> (vous ne pouvez pas retirer votre propre rôle).</p>
  <?php else: ?>
    <label>Rôle
      <select name="role" required>
        <?php foreach ($rolesDisponibles as $role): ?>
          <option value="<?= h($role) ?>" <?= $role === $donnees['role'] ? 'selected' : '' ?>>
            <?= h(ucfirst($role)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <button type="submit">Enregistrer les modifications</button>
</form>

<p><a href="liste.php">Retour à la gestion des utilisateurs</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
