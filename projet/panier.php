<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        $quantites = $_POST['quantites'] ?? [];
        $erreurs = panier_mettre_a_jour(is_array($quantites) ? $quantites : []);
        if ($erreurs === []) {
            flash_set('Panier mis à jour.');
            rediriger('panier.php');
        }
    }
}

$lignes = panier_lignes();
$total = array_sum(array_column($lignes, 'sous_total'));
$titre = 'Mon panier';
require __DIR__ . '/templates/header.php';
?>

<link rel="stylesheet" href="./assets/styles/panier.css">

<?php if ($lignes === []): ?>
    <section class="panier-vide">
        <p>Votre panier est vide.</p>
        <a class="bouton" href="produits/catalogue.php">Découvrir le catalogue</a>
    </section>
<?php else: ?>
    <form method="post" class="panier-form">
        <?= csrf_champ() ?>
        <div class="panier-lignes">
            <?php foreach ($lignes as $ligne): ?>
                <article class="ligne-panier">
                    <img src="<?= h($ligne['url_image_p'] ?: 'https://picsum.photos/120/120') ?>" alt="<?= h($ligne['designation_p']) ?>">
                    <div>
                        <h2><?= h($ligne['designation_p']) ?></h2>
                        <p><?= number_format((float) $ligne['prix_ttc'], 2, ',', ' ') ?> € TTC l'unité</p>
                    </div>
                    <label>
                        Quantité
                        <input type="number" name="quantites[<?= h((string) $ligne['p_id']) ?>]" min="0" max="<?= h((string) $ligne['stock_p']) ?>" value="<?= h((string) $ligne['quantite']) ?>">
                    </label>
                    <strong><?= number_format((float) $ligne['sous_total'], 2, ',', ' ') ?> €</strong>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="panier-actions">
            <a class="bouton secondaire" href="produits/catalogue.php">Continuer mes achats</a>
            <button type="submit">Mettre à jour le panier</button>
        </div>
    </form>

    <aside class="panier-total">
        <span>Total TTC</span>
        <strong><?= number_format((float) $total, 2, ',', ' ') ?> €</strong>
        <button type="button" disabled>Passer la commande</button>
    </aside>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
