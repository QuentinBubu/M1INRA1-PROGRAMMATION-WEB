<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';

$tlProduits = db()->query('SELECT * FROM produit')->fetchAll(PDO::FETCH_ASSOC);

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

$titre = 'Liste des produits';

require_once __DIR__ . '/../templates/header.php';

foreach ($tlProduits as $produit) {
    echo "Product ID: {$produit->getPId()}
        - Designation: {$produit->getDesignationP()} 
        - Stock: {$produit->getStockP()} 
        - Date d'entrée: {$produit->getDateIn()}
        - Image URL: {$produit->getUrlImageP()}
        - Promo: {$produit->getPromoP()}
        - Catégorie ID: {$produit->getCategorieId()}
        - Prix HT : {$produit->getDernierPrix()->getPrixHt()}
        - <a href='modification.php?id={$produit->getPId()}'>(Modifier)</a> <br>";
}

?>

<a href="ajout.php">Ajouter un produit</a>

<?php

require_once __DIR__ . '/../templates/footer.php';