<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
exiger_connexion();

$lignesPanier = panier_lignes();
if ($lignesPanier === []) {
    flash_set('Votre panier est vide.');
    rediriger('panier.php');
}

$stCompte = db()->prepare(
    'SELECT prenom_c, nom_c, adresse_livraison_c, telephone_c
     FROM compte WHERE user_id = :user_id'
);
$stCompte->execute(['user_id' => $_SESSION['user_id']]);
$compte = $stCompte->fetch() ?: [];

$adresse = (string) ($compte['adresse_livraison_c'] ?? '');
$telephone = (string) ($compte['telephone_c'] ?? '');
$numeroCarte = '';
$expiration = '';
$cvv = '';
$erreurs = [];

function carte_valide(string $numero): bool
{
    $somme = 0;
    $double = false;

    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $chiffre = (int) $numero[$i];
        if ($double) {
            $chiffre *= 2;
            if ($chiffre > 9) {
                $chiffre -= 9;
            }
        }
        $somme += $chiffre;
        $double = !$double;
    }

    return $somme % 10 === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim((string) ($_POST['adresse_livraison'] ?? ''));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $numeroCarte = preg_replace('/\D+/', '', (string) ($_POST['numero_carte'] ?? '')) ?? '';
    $expiration = trim((string) ($_POST['expiration'] ?? ''));
    $cvv = trim((string) ($_POST['cvv'] ?? ''));

    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    }
    if (mb_strlen($adresse) < 10 || mb_strlen($adresse) > 500) {
        $erreurs[] = 'L’adresse de livraison doit contenir entre 10 et 500 caractères.';
    }
    if ($telephone !== '' && !preg_match('/^\+?[0-9][0-9 .-]{7,18}$/', $telephone)) {
        $erreurs[] = 'Le numéro de téléphone est invalide.';
    }
    if (!preg_match('/^\d{13,19}$/', $numeroCarte) || !carte_valide($numeroCarte)) {
        $erreurs[] = 'Le numéro de carte est invalide.';
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiration, $match)) {
        $erreurs[] = 'La date d’expiration doit être au format MM/AA.';
    } else {
        $annee = 2000 + (int) $match[2];
        $mois = (int) $match[1];
        $limite = new DateTimeImmutable(sprintf('%04d-%02d-01', $annee, $mois));
        if ($limite < new DateTimeImmutable('first day of this month')) {
            $erreurs[] = 'La carte est expirée.';
        }
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $erreurs[] = 'Le cryptogramme visuel est invalide.';
    }

    if ($erreurs === []) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $produits = [];
            $totalHt = 0.0;
            $totalTva = 0.0;
            $totalTtc = 0.0;

            foreach ($lignesPanier as $lignePanier) {
                $stProduit = $pdo->prepare(
                    'SELECT p.p_id, p.designation_p, p.stock_p, p.promo_p, c.taxe_c,
                            COALESCE((SELECT ph.prix_ht
                                      FROM prix_historique ph
                                      WHERE ph.p_id = p.p_id
                                      ORDER BY ph.date_debut DESC, ph.prix_id DESC
                                      LIMIT 1), 0) AS prix_ht
                     FROM produit p
                     INNER JOIN categorie c USING (categorie_id)
                     WHERE p.p_id = :p_id AND p.is_deleted = 0
                     FOR UPDATE'
                );
                $stProduit->execute(['p_id' => (int) $lignePanier['p_id']]);
                $produit = $stProduit->fetch();
                $quantite = (int) $lignePanier['quantite'];

                if ($produit === false || (int) $produit['stock_p'] < $quantite) {
                    throw new RuntimeException(
                        'Le stock d’un article vient de changer. Vérifiez votre panier puis réessayez.'
                    );
                }

                $prixHt = (float) $produit['prix_ht'];
                $promo = $produit['promo_p'] !== null ? (int) $produit['promo_p'] : 0;
                if ($promo > 0) {
                    $prixHt *= 1 - ($promo / 100);
                }
                $prixHt = round($prixHt, 2);
                $tauxTva = (float) $produit['taxe_c'];
                $prixTtc = round($prixHt * (1 + $tauxTva), 2);
                $totalLigneHt = round($prixHt * $quantite, 2);
                $totalLigneTtc = round($prixTtc * $quantite, 2);

                $produits[] = [
                    'p_id' => (int) $produit['p_id'],
                    'designation' => (string) $produit['designation_p'],
                    'quantite' => $quantite,
                    'prix_ht' => $prixHt,
                    'taux_tva' => $tauxTva,
                    'total_ttc' => $totalLigneTtc,
                ];
                $totalHt += $totalLigneHt;
                $totalTtc += $totalLigneTtc;
                $totalTva += $totalLigneTtc - round($totalLigneHt, 2);
            }

            $totalHt = round($totalHt, 2);
            $totalTva = round($totalTva, 2);
            $totalTtc = round($totalTtc, 2);
            $numeroCommande = 'CMD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

            $stCommande = $pdo->prepare(
                'INSERT INTO commande
                    (numero_commande, user_id, adresse_livraison, telephone,
                     total_ht, total_tva, total_ttc, mode_paiement, carte_fin)
                 VALUES
                    (:numero, :user_id, :adresse, :telephone,
                     :total_ht, :total_tva, :total_ttc, :mode_paiement, :carte_fin)'
            );
            $stCommande->execute([
                'numero' => $numeroCommande,
                'user_id' => $_SESSION['user_id'],
                'adresse' => $adresse,
                'telephone' => $telephone !== '' ? $telephone : null,
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
                'mode_paiement' => 'carte',
                'carte_fin' => substr($numeroCarte, -4),
            ]);
            $commandeId = (int) $pdo->lastInsertId();

            $stLigne = $pdo->prepare(
                'INSERT INTO ligne_commande
                    (commande_id, p_id, designation, quantite, prix_unitaire_ht, taux_tva, total_ligne_ttc)
                 VALUES
                    (:commande_id, :p_id, :designation, :quantite, :prix_ht, :taux_tva, :total_ttc)'
            );
            $stStock = $pdo->prepare(
                'UPDATE produit SET stock_p = stock_p - :quantite
                 WHERE p_id = :p_id AND stock_p >= :quantite_stock'
            );
            foreach ($produits as $produit) {
                $stLigne->execute([
                    'commande_id' => $commandeId,
                    'p_id' => $produit['p_id'],
                    'designation' => $produit['designation'],
                    'quantite' => $produit['quantite'],
                    'prix_ht' => $produit['prix_ht'],
                    'taux_tva' => $produit['taux_tva'],
                    'total_ttc' => $produit['total_ttc'],
                ]);
                $stStock->execute([
                    'quantite' => $produit['quantite'],
                    'p_id' => $produit['p_id'],
                    'quantite_stock' => $produit['quantite'],
                ]);
                if ($stStock->rowCount() !== 1) {
                    throw new RuntimeException('Le stock d’un article est insuffisant.');
                }
            }

            $stCompte = $pdo->prepare(
                'UPDATE compte SET adresse_livraison_c = :adresse, telephone_c = :telephone
                 WHERE user_id = :user_id'
            );
            $stCompte->execute([
                'adresse' => $adresse,
                'telephone' => $telephone !== '' ? $telephone : null,
                'user_id' => $_SESSION['user_id'],
            ]);

            $pdo->commit();
            $_SESSION['panier'] = [];
            rediriger('commande_confirmation.php?numero=' . rawurlencode($numeroCommande));
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erreurs[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Création commande : ' . $e->getMessage());
            $erreurs[] = 'Le paiement n’a pas pu être finalisé. Veuillez réessayer.';
        }
    }
}

$titre = 'Paiement';
require __DIR__ . '/templates/header.php';
?>
<link rel="stylesheet" href="/assets/styles/commande.css">
<script src="/assets/js/commande.js" defer></script>

<div class="commande-layout">
    <section>
        <p class="etape-commande"><strong>1. Livraison</strong> <span>→</span> <strong>2. Paiement</strong> <span>→</span> Confirmation</p>
        <form method="post" class="commande-form" autocomplete="on">
            <?= csrf_champ() ?>
            <fieldset>
                <legend>Adresse de livraison</legend>
                <label for="adresse_livraison">Adresse complète</label>
                <textarea id="adresse_livraison" name="adresse_livraison" rows="4" required><?= h($adresse) ?></textarea>
                <label for="telephone">Téléphone (facultatif)</label>
                <input id="telephone" name="telephone" type="tel" value="<?= h($telephone) ?>" autocomplete="tel">
            </fieldset>

            <fieldset>
                <legend>Paiement par carte</legend>
                <p class="aide-paiement">Paiement de démonstration : aucune donnée bancaire complète n’est enregistrée.</p>
                <label for="numero_carte">Numéro de carte</label>
                <input id="numero_carte" name="numero_carte" inputmode="numeric" autocomplete="cc-number"
                       placeholder="4242 4242 4242 4242" maxlength="23" required>
                <div class="champs-paiement">
                    <label for="expiration">Expiration (MM/AA)
                        <input id="expiration" name="expiration" inputmode="numeric" autocomplete="cc-exp"
                               placeholder="MM/AA" maxlength="5" required>
                    </label>
                    <label for="cvv">Cryptogramme
                        <input id="cvv" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4" required>
                    </label>
                </div>
            </fieldset>
            <button type="submit">Payer <?= format_prix((float) array_sum(array_column($lignesPanier, 'sous_total'))) ?></button>
        </form>
    </section>

    <aside class="resume-commande">
        <h2>Résumé</h2>
        <?php foreach ($lignesPanier as $ligne): ?>
            <p><span><?= h($ligne['designation_p']) ?> × <?= h((string) $ligne['quantite']) ?></span>
                <strong><?= format_prix((float) $ligne['sous_total']) ?></strong></p>
        <?php endforeach; ?>
        <hr>
        <p class="resume-total"><span>Total TTC</span>
            <strong><?= format_prix((float) array_sum(array_column($lignesPanier, 'sous_total'))) ?></strong></p>
        <a class="bouton secondaire" href="panier.php">Retour au panier</a>
    </aside>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
