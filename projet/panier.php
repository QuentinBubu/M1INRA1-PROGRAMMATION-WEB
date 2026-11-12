<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        if (isset($_POST['supprimer'])) {
            $produitId = filter_var($_POST['supprimer'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($produitId === false) {
                $erreurs[] = 'Article invalide.';
            } else {
                $panier = panier();
                unset($panier[$produitId]);
                $_SESSION['panier'] = $panier;
                flash_set('Article supprimé du panier.');
                rediriger('panier.php');
            }
        }

        $quantites = $_POST['quantites'] ?? [];
        $erreurs = panier_mettre_a_jour(is_array($quantites) ? $quantites : []);
        if ($erreurs === []) {
            if (($_POST['action'] ?? '') === 'commander' && est_connecte()) {
                rediriger('commande.php');
            }
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
<script src="/assets/js/panier.js" defer></script>

<?php if ($lignes === []): ?>
    <section class="panier-vide">
        <p>Votre panier est vide.</p>
        <a class="bouton" href="produits/catalogue.php">Découvrir le catalogue</a>
    </section>
<?php else: ?>
    <form method="post" class="panier-form" id="panier-form">
        <?= csrf_champ() ?>
        <div class="panier-lignes">
            <?php foreach ($lignes as $ligne): ?>
                <article class="ligne-panier"
                         data-prix-unitaire="<?= h(number_format((float) $ligne['prix_ttc'], 2, '.', '')) ?>">
                    <img src="<?= h($ligne['url_image_p'] ?: image_defaut()) ?>" alt="<?= h($ligne['designation_p']) ?>">
                    <div>
                        <h2><?= h($ligne['designation_p']) ?></h2>
                        <p><?= number_format((float) $ligne['prix_ttc'], 2, ',', ' ') ?> € TTC l'unité</p>
                    </div>
                    <label>
                        Quantité
                        <input type="number" name="quantites[<?= h((string) $ligne['p_id']) ?>]" min="0" max="<?= h((string) $ligne['stock_p']) ?>" step="1"
                               data-quantite value="<?= h((string) $ligne['quantite']) ?>">
                    </label>
                    <strong data-ligne-total><?= number_format((float) $ligne['sous_total'], 2, ',', ' ') ?> €</strong>
                    <button type="submit" class="bouton-supprimer" name="supprimer"
                            value="<?= h((string) $ligne['p_id']) ?>"
                            aria-label="Supprimer <?= h($ligne['designation_p']) ?>"
                            title="Supprimer cet article">🗑</button>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="panier-actions">
            <a class="bouton secondaire" href="produits/catalogue.php">Continuer mes achats</a>
        </div>
    </form>

    <aside class="panier-total">
        <span>Total TTC</span>
        <strong data-total-panier aria-live="polite"><?= number_format((float) $total, 2, ',', ' ') ?> €</strong>
        <?php if (est_connecte()): ?>
            <button type="submit" form="panier-form" name="action" value="commander">Passer la commande</button>
        <?php else: ?>
            <a class="bouton" href="connexion.php">Connectez-vous pour commander</a>
        <?php endif; ?>
    </aside>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
