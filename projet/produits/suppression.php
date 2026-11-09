<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('/produits/liste.php');
}

if (!csrf_valide()) {
    flash_set('Requête invalide, veuillez réessayer.');
    rediriger('/produits/liste.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    flash_set('Produit introuvable.');
    rediriger('/produits/liste.php');
}

$st = db()->prepare('UPDATE produit SET is_deleted = 1 WHERE p_id = :id AND is_deleted = 0');
$st->execute(['id' => $id]);

flash_set($st->rowCount() > 0 ? 'Produit supprimé.' : 'Produit introuvable.');
rediriger('/produits/liste.php');
