<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('/categories/ajout.php');
}

if (!csrf_valide()) {
    flash_set('Requête invalide, veuillez réessayer.');
    rediriger('/categories/ajout.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    flash_set('Catégorie introuvable.');
    rediriger('/categories/ajout.php');
}

$st = db()->prepare('SELECT libelle_c FROM categorie WHERE categorie_id = :id LIMIT 1');
$st->execute(['id' => $id]);
$categorie = $st->fetch();

if ($categorie === false) {
    flash_set('Catégorie introuvable.');
    rediriger('/categories/ajout.php');
}

// La clé étrangère produit.categorie_id est en ON DELETE RESTRICT :
// on refuse proprement la suppression si des produits utilisent la catégorie.
$st = db()->prepare('SELECT COUNT(*) FROM produit WHERE categorie_id = :id');
$st->execute(['id' => $id]);
$produits = (int) $st->fetchColumn();

if ($produits > 0) {
    flash_set('Impossible de supprimer cette catégorie : ' . $produits . ' produit(s) y sont encore associés.');
    rediriger('/categories/ajout.php');
}

try {
    $st = db()->prepare('DELETE FROM categorie WHERE categorie_id = :id');
    $st->execute(['id' => $id]);
    flash_set('Catégorie et TVA supprimées.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        flash_set('Impossible de supprimer cette catégorie : des produits y sont encore associés.');
    } else {
        error_log('Suppression catégorie : ' . $e->getMessage());
        flash_set('Impossible de supprimer la catégorie.');
    }
}

rediriger('/categories/ajout.php');
