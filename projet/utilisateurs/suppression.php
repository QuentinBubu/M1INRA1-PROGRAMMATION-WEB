<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('/utilisateurs/liste.php');
}

if (!csrf_valide()) {
    flash_set('Requête invalide, veuillez réessayer.');
    rediriger('/utilisateurs/liste.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    flash_set('Utilisateur introuvable.');
    rediriger('/utilisateurs/liste.php');
}

$st = db()->prepare('SELECT user_login FROM `user` WHERE user_id = :id LIMIT 1');
$st->execute(['id' => $id]);
$utilisateur = $st->fetch();

if ($utilisateur === false) {
    flash_set('Utilisateur introuvable.');
    rediriger('/utilisateurs/liste.php');
}

if ($id === (int) $_SESSION['user_id']) {
    flash_set('Vous ne pouvez pas supprimer votre propre compte.');
    rediriger('/utilisateurs/liste.php');
}

if (in_array('admin', roles_utilisateur($id), true) && compter_admins() <= 1) {
    flash_set('Impossible de supprimer le dernier administrateur.');
    rediriger('/utilisateurs/liste.php');
}

try {
    $st = db()->prepare('DELETE FROM `user` WHERE user_id = :id');
    $st->execute(['id' => $id]);
    flash_set('Utilisateur supprimé.');
} catch (PDOException $e) {
    error_log('Suppression utilisateur : ' . $e->getMessage());
    flash_set('Impossible de supprimer cet utilisateur.');
}

rediriger('/utilisateurs/liste.php');
