<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Accès direct à la base pour préparer et nettoyer les données de test.
 *
 * Les parcours testés passent par le navigateur ; cette classe sert uniquement
 * à créer des fixtures et à supprimer les données après les tests.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $env = self::chargerEnv(dirname(__DIR__, 2) . '/.env');
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('MYSQL_DATABASE') ?: ($env['MYSQL_DATABASE'] ?? 'e_commerce');
        $dbUser = getenv('MYSQL_USER') ?: ($env['MYSQL_USER'] ?? 'web_user');
        $dbPass = getenv('MYSQL_PASSWORD') ?: ($env['MYSQL_PASSWORD'] ?? '');

        self::$pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=utf8mb4',
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$pdo;
    }

    /**
     * Crée un produit de test avec son prix courant.
     */
    public static function creerProduit(
        string $designation,
        float $prixHt,
        int $stock,
        int $categorieId,
        ?int $promo = null
    ): int {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $st = $pdo->prepare(
                'INSERT INTO produit (designation_p, date_in, stock_p, url_image_p, promo_p, categorie_id)
                 VALUES (:designation, :date_in, :stock, NULL, :promo, :categorie_id)'
            );
            $st->execute([
                'designation' => $designation,
                'date_in' => date('Y-m-d'),
                'stock' => $stock,
                'promo' => $promo,
                'categorie_id' => $categorieId,
            ]);

            $produitId = (int) $pdo->lastInsertId();

            $stPrix = $pdo->prepare(
                'INSERT INTO prix_historique (p_id, prix_ht)
                 VALUES (:p_id, :prix_ht)'
            );
            $stPrix->execute([
                'p_id' => $produitId,
                'prix_ht' => $prixHt,
            ]);

            $pdo->commit();

            return $produitId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Supprime définitivement un produit de test et son historique de prix.
     */
    public static function supprimerProduitParDesignation(string $designation): void
    {
        $st = self::pdo()->prepare('DELETE FROM produit WHERE designation_p = :designation');
        $st->execute(['designation' => $designation]);
    }

    /**
     * Retourne une catégorie existante pour les fixtures.
     */
    public static function premiereCategorieId(): int
    {
        $id = self::pdo()
            ->query('SELECT categorie_id FROM categorie ORDER BY categorie_id LIMIT 1')
            ->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('Aucune catégorie disponible pour créer un produit de test.');
        }

        return (int) $id;
    }

    /**
     * Charge un fichier .env simple (clé=valeur).
     *
     * @return array<string, string>
     */
    private static function chargerEnv(string $chemin): array
    {
        if (!is_file($chemin)) {
            return [];
        }

        $valeurs = [];
        $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lignes === false) {
            return [];
        }

        foreach ($lignes as $ligne) {
            $ligne = trim($ligne);
            if ($ligne === '' || str_starts_with($ligne, '#')) {
                continue;
            }

            [$cle, $valeur] = array_pad(explode('=', $ligne, 2), 2, '');
            $cle = trim($cle);
            if ($cle === '') {
                continue;
            }

            $valeurs[$cle] = trim($valeur, " \t\n\r\0\x0B\"'");
        }

        return $valeurs;
    }
}
