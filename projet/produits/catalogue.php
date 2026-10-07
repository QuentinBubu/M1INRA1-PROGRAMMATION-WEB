<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';

$tlProduits = db()->query('SELECT * FROM produit WHERE is_deleted = 0')->fetchAll(PDO::FETCH_ASSOC);

$tlProduits = array_map(function ($produit) {
    $entity = new ProductEntity();
    $requetePrix = db()->prepare(
        'SELECT prix_ht
         FROM prix_historique
         WHERE p_id = :p_id
         ORDER BY date_debut DESC, prix_id DESC
         LIMIT 1'
    );
    $requetePrix->execute(['p_id' => $produit['p_id']]);
    $prix = $requetePrix->fetch(PDO::FETCH_ASSOC);

    $prixEntity = new PrixEntity();
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
            ->setCategorieId((int)$produit['categorie_id'])
            ->setDesignationP($produit['designation_p']);
    return $entity;
}, $tlProduits);

$titre = 'Catalogue';

require_once __DIR__ . '/../templates/header.php';

?>

<link rel="stylesheet" href="../assets/styles/catalogue.css">

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
            <p>Catégorie ID: <?= h($produit->getCategorieId()) ?></p>
            <p>Prix HT / TTC : <?= h($produit->getDernierPrix()->getPrixHt()) ?>
                / <?= h($produit->getDernierPrix()->getPrixTtc()) ?></p>
        </article>
    <?php endforeach; endif; ?>
</section>

<?php

require_once __DIR__ . '/../templates/footer.php';
