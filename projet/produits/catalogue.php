<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Entities/ProductEntity.php';
require_once __DIR__ . '/../Entities/PrixEntity.php';
require_once __DIR__ . '/../Entities/CategorieEntity.php';

$erreurs = [];

// Catégories disponibles pour le tri par type de produit.
$categories = db()->query(
    'SELECT categorie_id, libelle_c
     FROM categorie
     ORDER BY libelle_c'
)->fetchAll();
$categorieIds = array_map('intval', array_column($categories, 'categorie_id'));

// Filtre sélectionné : identifiant entier existant, sinon aucun filtre.
$categorieFiltre = filter_var(
    $_GET['categorie'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if ($categorieFiltre === false || !in_array($categorieFiltre, $categorieIds, true)) {
    $categorieFiltre = null;
}

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

            // On conserve le filtre après l'ajout au panier.
            $categorieRetour = filter_var(
                $_POST['categorie'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($categorieRetour !== false && in_array($categorieRetour, $categorieIds, true)) {
                $categorieFiltre = $categorieRetour;
            }

            $url = '/produits/catalogue.php';
            if ($categorieFiltre !== null) {
                $url .= '?categorie=' . $categorieFiltre;
            }
            rediriger($url);
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
SQL;

$parametres = [];
if ($categorieFiltre !== null) {
    $sql .= ' AND p.categorie_id = :categorie_id';
    $parametres['categorie_id'] = $categorieFiltre;
}

// Sans filtre : tri par type puis par désignation.
// Avec filtre : tri par désignation dans le type choisi.
$sql .= $categorieFiltre === null
    ? ' ORDER BY c.libelle_c ASC, p.designation_p ASC'
    : ' ORDER BY p.designation_p ASC';

$st = db()->prepare($sql);
$st->execute($parametres);

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
}, $st->fetchAll());

$titre = 'Catalogue';

require_once __DIR__ . '/../templates/header.php';
?>

<link rel="stylesheet" href="../assets/styles/catalogue.css">
<script src="/assets/js/catalogue.js" defer></script>

<form method="get" class="filtre-catalogue">
    <label for="categorie">Trier par type de produit</label>
    <select name="categorie" id="categorie">
        <option value="">Tous les types</option>
        <?php foreach ($categories as $categorie): ?>
            <option value="<?= h((string) $categorie['categorie_id']) ?>"
                <?= $categorieFiltre === (int) $categorie['categorie_id'] ? 'selected' : '' ?>>
                <?= h($categorie['libelle_c']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php // Bouton de secours si JavaScript est désactivé. ?>
    <noscript>
        <button type="submit">Trier</button>
    </noscript>
</form>

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
                    src="<?= h($produit->getUrlImageP() ?: image_defaut()) ?>"
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
                        <?php if ($categorieFiltre !== null): ?>
                            <input type="hidden" name="categorie" value="<?= h((string) $categorieFiltre) ?>">
                        <?php endif; ?>
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
