<?php

declare(strict_types=1);

/* ========== Configuration ========== */
const DB_HOST = 'db';
const DB_NAME = 'e_commerce';
const DB_USER = 'web_user';
const DB_PASS = 'masterApp2809';

const SESSION_INACTIVITE = 1800;   // 30 min sans activite => déconnexion

ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/* ========== En-têtes de sécurité ========== */
header('Referrer-Policy: same-origin');

session_start();

/* Expiration par inactivité */
if (isset($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_INACTIVITE) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash'] = 'Session expirée, veuillez vous reconnecter.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}

/* ========== Connexion PDO ========== */
/**
 * Retourne la connexion PDO partagee a la base de donnees.
 *
 * @return PDO Connexion PDO configuree pour l'application.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Connexion BDD : ' . $e->getMessage());
            http_response_code(500);
            exit('Service indisponible');
        }
    }
    return $pdo;
}

require_once __DIR__ . '/functions.php';
