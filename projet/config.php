<?php

declare(strict_types=1);

/* ========== Configuration ========== */
/* Les secrets viennent du fichier .env (injecté par docker-compose). */
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'e_commerce');
define('DB_USER', getenv('MYSQL_USER') ?: 'web_user');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');

const SESSION_INACTIVITE = 1800;          // 30 min sans activité => déconnexion

ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/* ========== En-têtes de sécurité ========== */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "img-src 'self' https: data:; "
    . "style-src 'self' 'unsafe-inline'; "
    . "script-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'none'"
);

/* ========== Session sécurisée ========== */
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_start();
}

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
 * Retourne la connexion PDO partagée à la base de données.
 *
 * @return PDO Connexion PDO configurée pour l'application.
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
