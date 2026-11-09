<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$utilisateurs = db()->query(
    'SELECT u.user_id, u.user_login, u.user_mail, u.user_date_new,
            c.prenom_c, c.nom_c,
            GROUP_CONCAT(r.libelle_role ORDER BY r.libelle_role SEPARATOR \', \') AS roles
     FROM `user` u
     LEFT JOIN compte c ON c.user_id = u.user_id
     LEFT JOIN droit d ON d.user_id = u.user_id
     LEFT JOIN role r ON r.id_role = d.id_role
     GROUP BY u.user_id, u.user_login, u.user_mail, u.user_date_new, c.prenom_c, c.nom_c
     ORDER BY u.user_id'
)->fetchAll();

$titre = 'Gestion des utilisateurs';
require_once __DIR__ . '/../templates/header.php';
?>

<p><a class="bouton" href="ajout.php">Ajouter un utilisateur</a></p>

<?php if ($utilisateurs === []): ?>
  <p>Aucun utilisateur enregistré.</p>
<?php else: ?>
  <div class="table-wrapper">
    <table class="table-admin">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Identifiant</th>
          <th scope="col">E-mail</th>
          <th scope="col">Prénom</th>
          <th scope="col">Nom</th>
          <th scope="col">Rôle(s)</th>
          <th scope="col">Inscrit le</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $utilisateur): ?>
          <?php $estCourant = (int) $utilisateur['user_id'] === (int) $_SESSION['user_id']; ?>
          <tr>
            <td><?= h((string) $utilisateur['user_id']) ?></td>
            <td><?= h($utilisateur['user_login']) ?><?= $estCourant ? ' (vous)' : '' ?></td>
            <td><?= h($utilisateur['user_mail']) ?></td>
            <td><?= h($utilisateur['prenom_c'] ?? '—') ?></td>
            <td><?= h($utilisateur['nom_c'] ?? '—') ?></td>
            <td><?= h($utilisateur['roles'] ?? '—') ?></td>
            <td><?= h(format_date_fr(substr((string) $utilisateur['user_date_new'], 0, 10))) ?></td>
            <td class="actions">
              <a href="modification.php?id=<?= h((string) $utilisateur['user_id']) ?>">Modifier</a>
              <?php if (!$estCourant): ?>
                <form method="post" action="suppression.php" data-confirm="Supprimer cet utilisateur ?">
                  <?= csrf_champ() ?>
                  <input type="hidden" name="id" value="<?= h((string) $utilisateur['user_id']) ?>">
                  <button type="submit">Supprimer</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<p><a href="../produits/liste.php">Retour à la gestion des produits</a></p>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
