<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';

$tlProduits = db()->query('SELECT * FROM produit')->fetchAll(PDO::FETCH_ASSOC);

$tlProduits = array_map(function ($produit) {
    $entity = new ProductEntity();
    $entity->setPId((int)$produit['p_id']);
    $entity->setDesignationP($produit['designation_p']);
    $entity->setDateIn($produit['date_in']);
    $entity->setTimesIn($produit['times_in']);
    $entity->setStockP((int)$produit['stock_p']);
    $entity->setUrlImageP($produit['url_image_p']);
    $entity->setPromoP((int)$produit['promo_p']);
    $entity->setCategorieId((int)$produit['categorie_id']);
    return $entity;
}, $tlProduits);

$titre = 'Liste des produits';

require_once __DIR__ . '/../templates/header.php';

foreach ($tlProduits as $produit) {
    echo "Product ID: {$produit->getPId()} - Designation: {$produit->getDesignationP()} - Stock: {$produit->getStockP()} - <a href='modification.php?id={$produit->getPId()}'>(Modifier)</a> <br>";
}

?>

<a href="ajout.php">Ajouter un produit</a>

<?php

require_once __DIR__ . '/../templates/footer.php';