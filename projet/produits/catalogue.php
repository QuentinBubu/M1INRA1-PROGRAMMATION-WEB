<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';
require_once __DIR__ . '/../Entities/PrixEntity.php';
require_once __DIR__ . '/../Entities/CategorieEntity.php';

$erreurs = [];

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

$sql = <<<'SQL'
SELECT
    p.p_id,
    p.designation_p,
    p.date_in,
    p.times_in,
    p.stock_p,
    p.url_image_p,
    p.promo_p,
    p.categorie_id,
    c.libelle_c,
    c.taxe_c,
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
ORDER BY p.designation_p ASC
SQL;

$tlProduits = array_map(static function (array $produit): ProductEntity {
    $categorie = (new CategorieEntity())
        ->setCategorieId((int) $produit['categorie_id'])
        ->setLibelleC($produit['libelle_c'])
        ->setTaxeC((float) $produit['taxe_c']);

    $entity = (new ProductEntity())
        ->setPId((int) $produit['p_id'])
        ->setDesignationP($produit['designation_p'])
        ->setDateIn($produit['date_in'])
        ->setTimesIn($produit['times_in'])
        ->setStockP((int) $produit['stock_p'])
        ->setUrlImageP($produit['url_image_p'])
        ->setPromoP($produit['promo_p'] !== null ? (int) $produit['promo_p'] : null)
        ->setCategorie($categorie);

    if ($produit['prix_ht'] !== null) {
        $entity->setDernierPrix((new PrixEntity())->setPrixHt((float) $produit['prix_ht']));
    }

    return $entity;
}, db()->query($sql)->fetchAll());

$titre = 'Catalogue';

require_once __DIR__ . '/../templates/header.php';
?>

<link rel="stylesheet" href="../assets/styles/catalogue.css">

<p class="lien-panier">
    <a class="bouton secondaire" href="../panier.php">
        Voir mon panier (<?= h((string) panier_quantite()) ?>)
    </a>
</p>

<section>
    <?php if ($tlProduits === []): ?>
        <p>Aucun produit trouvé.</p>
    <?php else: ?>
        <?php foreach ($tlProduits as $produit): ?>
            <article class="produit">
                <h2><?= h($produit->getDesignationP()) ?></h2>
                <img
                    src="<?= h($produit->getUrlImageP() ?: 'https://picsum.photos/200/300') ?>"
                    alt="<?= h($produit->getDesignationP()) ?>"
                >
                <p>Stock : <?= h((string) $produit->getStockP()) ?></p>
                <p>Date d'entrée : <?= h(format_date_fr($produit->getDateIn())) ?></p>
                <p>Catégorie : <?= h($produit->getCategorie()?->getLibelleC() ?? 'Non classé') ?></p>
                <?php if ($produit->getPromoP()): ?>
                    <p class="promo">Promo −<?= h((string) $produit->getPromoP()) ?> %</p>
                <?php endif; ?>
                <?php if ($produit->hasPrix()): ?>
                    <p class="prix">
                        Prix HT / TTC :
                        <?= h(format_prix($produit->calcDernierPrix(false))) ?>
                        /
                        <?= h(format_prix($produit->calcDernierPrix())) ?>
                    </p>
                <?php else: ?>
                    <p class="prix">Prix indisponible</p>
                <?php endif; ?>
                <?php if ($produit->getStockP() < 1): ?>
                    <p class="rupture">Rupture de stock</p>
                <?php elseif ($produit->hasPrix()): ?>
                    <form method="post">
                        <?= csrf_champ() ?>
                        <input type="hidden" name="p_id" value="<?= h((string) $produit->getPId()) ?>">
                        <label>
                            Quantité
                            <input
                                type="number"
                                name="quantite"
                                min="1"
                                max="<?= h((string) $produit->getStockP()) ?>"
                                value="1"
                            >
                        </label>
                        <button type="submit">Ajouter au panier</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/../templates/footer.php';
