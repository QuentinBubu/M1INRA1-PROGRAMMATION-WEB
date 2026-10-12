<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';
require_once __DIR__ . '/../Entities/PrixEntity.php';
require_once __DIR__ . '/../Entities/CategorieEntity.php';

$tlProduits = db()->query('SELECT * FROM produit WHERE is_deleted = 0')->fetchAll(PDO::FETCH_ASSOC);
$tlCategories = db()->query('SELECT * FROM categorie')->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_UNIQUE, \CategorieEntity::class);

$tlProduits = array_map(function ($produit) use ($tlCategories) {
    $entity = new \ProductEntity();
    $requetePrix = db()->prepare(
        'SELECT prix_ht
         FROM prix_historique
         WHERE p_id = :p_id
         ORDER BY date_debut DESC, prix_id DESC
         LIMIT 1'
    );
    $requetePrix->execute(['p_id' => $produit['p_id']]);
    $prix = $requetePrix->fetch(PDO::FETCH_ASSOC);

    $prixEntity = new \PrixEntity();
    if ($prix !== false) {
        $prixEntity->setPrixHt((float) $prix['prix_ht']);
    } else $prixEntity->setPrixHt(-1);

    $entity->setDernierPrix($prixEntity)
            ->setPId((int)$produit['p_id'])
            ->setDateIn($produit['date_in'])
            ->setTimesIn($produit['times_in'])
            ->setStockP((int)$produit['stock_p'])
            ->setUrlImageP($produit['url_image_p'])
            ->setPromoP((int)$produit['promo_p'])
            ->setCategorie($tlCategories[(int)$produit['categorie_id']] ?? null)
            ->setDesignationP($produit['designation_p']);
    return $entity;
}, $tlProduits);

$titre = 'Catalogue';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        $message = panier_ajouter(
            (int) ($_POST['p_id'] ?? 0),
            max(1, (int) ($_POST['quantite'] ?? 1))
        );
        if ($message !== null) {
            $erreurs[] = $message;
        } else {
            flash_set('Produit ajouté au panier.');
            rediriger('/produits/catalogue.php');
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<link rel="stylesheet" href="../assets/styles/catalogue.css">

<p class="lien-panier"><a class="bouton secondaire" href="../panier.php">Voir mon panier (<?= h((string) panier_quantite()) ?>)</a></p>

<section>
    <?php if (count($tlProduits) === 0): ?>
        <p>Aucun produit trouvé.</p>
    <?php else: foreach ($tlProduits as $produit): ?>
        <article class="produit">
            <h2><?= h($produit->getDesignationP()) ?></h2>
            <p>Stock: <?= h($produit->getStockP()) ?></p>
            <p>Date d'entrée: <?= h($produit->getDateIn()) ?></p>
            <img src="<?= h($produit->getUrlImageP() ?: 'https://picsum.photos/200/300') ?>" alt="<?= h($produit->getDesignationP()) ?>">
            <p>Promo: <?= h($produit->getPromoP()) ?></p>
            <p>Catégorie: <?= h($produit->getCategorie()->getLibellec()) ?></p>
            <p>Prix HT / TTC : <?= h($produit->calcDernierPrix(false)) ?>
                / <?= h($produit->calcDernierPrix()) ?></p>
            <?php if ($produit->getStockP() > 0): ?>
                <form method="post">
                    <?= csrf_champ() ?>
                    <input type="hidden" name="p_id" value="<?= h((string) $produit->getPId()) ?>">
                    <label>
                        Quantité
                        <input type="number" name="quantite" min="1" max="<?= h((string) $produit->getStockP()) ?>" value="1">
                    </label>
                    <button type="submit">Ajouter au panier</button>
                </form>
            <?php else: ?>
                <p class="rupture">Rupture de stock</p>
            <?php endif; ?>
        </article>
    <?php endforeach; endif; ?>
</section>

<?php

require_once __DIR__ . '/../templates/footer.php';
