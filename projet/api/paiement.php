<?php

declare(strict_types=1);

require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function reponse_json(int $statut, array $donnees): never
{
    http_response_code($statut);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function numero_carte_valide(string $numero): bool
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reponse_json(405, ['erreur' => 'Méthode POST requise.']);
}
if (!est_connecte()) {
    reponse_json(401, ['erreur' => 'Connexion obligatoire.']);
}

$contenu = file_get_contents('php://input');
$donnees = json_decode($contenu ?: '', true);
if (!is_array($donnees)) {
    reponse_json(400, ['erreur' => 'Le corps doit être un JSON valide.']);
}

$adresse = trim((string) ($donnees['adresse_livraison'] ?? ''));
$telephone = trim((string) ($donnees['telephone'] ?? ''));
$numeroCarte = preg_replace('/\D+/', '', (string) ($donnees['numero_carte'] ?? '')) ?? '';
$expiration = trim((string) ($donnees['expiration'] ?? ''));
$cvv = trim((string) ($donnees['cvv'] ?? ''));

$erreurs = [];
if (mb_strlen($adresse) < 10 || mb_strlen($adresse) > 500) {
    $erreurs[] = 'L’adresse de livraison doit contenir entre 10 et 500 caractères.';
}
if (!preg_match('/^\d{13,19}$/', $numeroCarte) || !numero_carte_valide($numeroCarte)) {
    $erreurs[] = 'Le numéro de carte est invalide.';
}
if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiration, $match)) {
    $erreurs[] = 'La date d’expiration doit être au format MM/AA.';
} elseif (new DateTimeImmutable(sprintf('%04d-%02d-01', 2000 + (int) $match[2], (int) $match[1])) < new DateTimeImmutable('first day of this month')) {
    $erreurs[] = 'La carte est expirée.';
}
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    $erreurs[] = 'Le cryptogramme visuel est invalide.';
}
if ($erreurs !== []) {
    reponse_json(422, ['erreurs' => $erreurs]);
}

$lignesPanier = panier_lignes();
if ($lignesPanier === []) {
    reponse_json(422, ['erreur' => 'Le panier est vide.']);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $produits = [];
    $totalHt = 0.0;
    $totalTva = 0.0;
    $totalTtc = 0.0;

    foreach ($lignesPanier as $lignePanier) {
        $st = $pdo->prepare(
            'SELECT p.p_id, p.designation_p, p.stock_p, p.promo_p, c.taxe_c,
                    COALESCE((SELECT ph.prix_ht FROM prix_historique ph
                              WHERE ph.p_id = p.p_id
                              ORDER BY ph.date_debut DESC, ph.prix_id DESC LIMIT 1), 0) AS prix_ht
             FROM produit p INNER JOIN categorie c USING (categorie_id)
             WHERE p.p_id = :p_id AND p.is_deleted = 0 FOR UPDATE'
        );
        $st->execute(['p_id' => (int) $lignePanier['p_id']]);
        $produit = $st->fetch();
        $quantite = (int) $lignePanier['quantite'];
        if ($produit === false || (int) $produit['stock_p'] < $quantite) {
            throw new RuntimeException('Stock insuffisant pour un article.');
        }

        $prixHt = (float) $produit['prix_ht'];
        $promo = $produit['promo_p'] !== null ? (int) $produit['promo_p'] : 0;
        $prixHt = round($promo > 0 ? $prixHt * (1 - $promo / 100) : $prixHt, 2);
        $tva = (float) $produit['taxe_c'];
        $prixTtc = round($prixHt * (1 + $tva), 2);
        $ligneHt = round($prixHt * $quantite, 2);
        $ligneTtc = round($prixTtc * $quantite, 2);
        $produits[] = [
            'p_id' => (int) $produit['p_id'],
            'designation' => (string) $produit['designation_p'],
            'quantite' => $quantite,
            'prix_ht' => $prixHt,
            'tva' => $tva,
            'total_ttc' => $ligneTtc,
        ];
        $totalHt += $ligneHt;
        $totalTtc += $ligneTtc;
        $totalTva += $ligneTtc - $ligneHt;
    }

    $numero = 'CMD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $st = $pdo->prepare(
        'INSERT INTO commande
         (numero_commande, user_id, adresse_livraison, telephone, total_ht, total_tva,
          total_ttc, mode_paiement, carte_fin)
         VALUES (:numero, :user_id, :adresse, :telephone, :total_ht, :total_tva,
                 :total_ttc, :mode, :carte_fin)'
    );
    $st->execute([
        'numero' => $numero,
        'user_id' => $_SESSION['user_id'],
        'adresse' => $adresse,
        'telephone' => $telephone !== '' ? $telephone : null,
        'total_ht' => round($totalHt, 2),
        'total_tva' => round($totalTva, 2),
        'total_ttc' => round($totalTtc, 2),
        'mode' => 'carte_api',
        'carte_fin' => substr($numeroCarte, -4),
    ]);
    $commandeId = (int) $pdo->lastInsertId();
    $stLigne = $pdo->prepare(
        'INSERT INTO ligne_commande
         (commande_id, p_id, designation, quantite, prix_unitaire_ht, taux_tva, total_ligne_ttc)
         VALUES (:commande_id, :p_id, :designation, :quantite, :prix_ht, :tva, :total_ttc)'
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
            'tva' => $produit['tva'],
            'total_ttc' => $produit['total_ttc'],
        ]);
        $stStock->execute([
            'quantite' => $produit['quantite'],
            'quantite_stock' => $produit['quantite'],
            'p_id' => $produit['p_id'],
        ]);
        if ($stStock->rowCount() !== 1) {
            throw new RuntimeException('Le stock a changé pendant le paiement.');
        }
    }

    $pdo->commit();
    $_SESSION['panier'] = [];
    reponse_json(201, [
        'message' => 'Paiement fictif accepté.',
        'commande' => [
            'numero' => $numero,
            'total_ttc' => round($totalTtc, 2),
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    reponse_json(409, ['erreur' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('API paiement : ' . $e->getMessage());
    reponse_json(500, ['erreur' => 'Le paiement n’a pas pu être finalisé.']);
}
