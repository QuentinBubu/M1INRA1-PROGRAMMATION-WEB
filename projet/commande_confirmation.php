<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
exiger_connexion();

$numero = trim((string) ($_GET['numero'] ?? ''));
$st = db()->prepare(
    'SELECT numero_commande, total_ttc, date_commande
     FROM commande
     WHERE numero_commande = :numero AND user_id = :user_id
     LIMIT 1'
);
$st->execute([
    'numero' => $numero,
    'user_id' => $_SESSION['user_id'],
]);
$commande = $st->fetch();

if ($commande === false) {
    http_response_code(404);
    $titre = 'Commande introuvable';
    require __DIR__ . '/templates/header.php';
    echo '<p>Cette commande n’existe pas ou ne vous appartient pas.</p>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$titre = 'Commande confirmée';
require __DIR__ . '/templates/header.php';
?>
<section class="confirmation-commande">
    <div class="confirmation-icone" aria-hidden="true">✓</div>
    <h2>Merci pour votre commande !</h2>
    <p>Votre paiement a été accepté et votre commande est en préparation.</p>
    <dl>
        <dt>Numéro de commande</dt>
        <dd><strong><?= h($commande['numero_commande']) ?></strong></dd>
        <dt>Total payé</dt>
        <dd><?= format_prix((float) $commande['total_ttc']) ?></dd>
        <dt>Date</dt>
        <dd><?= h((string) $commande['date_commande']) ?></dd>
    </dl>
    <a class="bouton" href="produits/catalogue.php">Retourner au catalogue</a>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
