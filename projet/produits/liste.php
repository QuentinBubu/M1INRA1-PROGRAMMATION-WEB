<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$produits = db()->query(
    'SELECT p.p_id, p.designation_p, p.stock_p, p.date_in, p.promo_p, p.url_image_p,
            c.libelle_c,
            (
                SELECT ph.prix_ht
                FROM prix_historique ph
                WHERE ph.p_id = p.p_id
                ORDER BY ph.date_debut DESC, ph.prix_id DESC
                LIMIT 1
            ) AS prix_ht
     FROM produit p
     INNER JOIN categorie c ON c.categorie_id = p.categorie_id
     WHERE p.is_deleted = 0
     ORDER BY p.p_id DESC'
)->fetchAll();

$titre = 'Gestion des produits';
require_once __DIR__ . '/../templates/header.php';
?>

<p class="lien-panier">
  <a class="bouton" href="ajout.php">Ajouter un produit</a>
  <a class="bouton secondaire" href="catalogue.php">Voir le catalogue</a>
</p>

<?php if ($produits === []): ?>
  <p>Aucun produit enregistré pour le moment.</p>
<?php else: ?>
  <div class="table-wrapper">
    <table class="table-admin">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Désignation</th>
          <th scope="col">Catégorie</th>
          <th scope="col">Prix HT</th>
          <th scope="col">Promo</th>
          <th scope="col">Stock</th>
          <th scope="col">Date d'entrée</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($produits as $produit): ?>
          <tr>
            <td><?= h((string) $produit['p_id']) ?></td>
            <td><?= h($produit['designation_p']) ?></td>
            <td><?= h($produit['libelle_c']) ?></td>
            <td>
              <?= $produit['prix_ht'] !== null ? h(format_prix((float) $produit['prix_ht'])) : '—' ?>
            </td>
            <td><?= $produit['promo_p'] !== null ? h((string) $produit['promo_p']) . ' %' : '—' ?></td>
            <td><?= h((string) $produit['stock_p']) ?></td>
            <td><?= h(format_date_fr($produit['date_in'])) ?></td>
            <td class="actions">
              <a href="modification.php?id=<?= h((string) $produit['p_id']) ?>">Modifier</a>
              <form method="post" action="suppression.php" data-confirm="Supprimer ce produit ?">
                <?= csrf_champ() ?>
                <input type="hidden" name="id" value="<?= h((string) $produit['p_id']) ?>">
                <button type="submit">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
