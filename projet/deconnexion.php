<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

// Déconnexion uniquement en POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valide()) {
    deconnecter();
    session_start();
    flash_set('Vous êtes déconnecté.');
    rediriger('connexion.php');
}
rediriger(est_connecte() ? 'profil.php' : 'connexion.php');
