<?php

declare(strict_types=1);

use Faker\Factory;

/**
 * Génère des produits de démonstration avec Faker.
 *
 * Utilisation (depuis la racine du projet, sur l'hôte) :
 *   php scripts/seed_produits.php
 *   php scripts/seed_produits.php 50
 *
 * Le script lit les identifiants MySQL dans .env.
 * Par défaut, il se connecte à 127.0.0.1 (port exposé par Docker).
 * Pour l'exécuter dans un conteneur, définir DB_HOST=db.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Dépendances manquantes. Lancez d'abord : composer install\n");
    exit(1);
}
require_once $autoload;

/**
 * Charge un fichier .env simple (clé=valeur) sans écraser les variables déjà définies.
 *
 * @return array<string, string>
 */
function charger_env(string $chemin): array
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

$env = charger_env(__DIR__ . '/../.env');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('MYSQL_DATABASE') ?: ($env['MYSQL_DATABASE'] ?? 'e_commerce');
$dbUser = getenv('MYSQL_USER') ?: ($env['MYSQL_USER'] ?? 'web_user');
$dbPass = getenv('MYSQL_PASSWORD') ?: ($env['MYSQL_PASSWORD'] ?? '');

$nombre = 20;
if (isset($argv[1])) {
    $nombre = filter_var($argv[1], FILTER_VALIDATE_INT);
    if ($nombre === false) {
        fwrite(STDERR, "Le nombre de produits doit être un entier.\n");
        exit(1);
    }
}

if ($nombre < 1 || $nombre > 500) {
    fwrite(STDERR, "Le nombre de produits doit être compris entre 1 et 500.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $categories = $pdo->query(
        'SELECT categorie_id, libelle_c
         FROM categorie
         ORDER BY categorie_id'
    )->fetchAll();

    if ($categories === []) {
        fwrite(STDERR, "Aucune catégorie trouvée. Ajoutez au moins une catégorie avant de générer des produits.\n");
        exit(1);
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Connexion à la base impossible : " . $e->getMessage() . "\n");
    fwrite(STDERR, "Vérifiez .env et que MySQL est démarré (docker compose up -d).\n");
    exit(1);
}

// Modèles réalistes par catégorie, pour éviter des désignations purement aléatoires.
$modelesParCategorie = [
    'Smartphone' => [
        'iPhone 15 128 Go',
        'iPhone 15 Pro 256 Go',
        'iPhone 14 128 Go',
        'Samsung Galaxy S24 256 Go',
        'Samsung Galaxy S23 128 Go',
        'Google Pixel 8 128 Go',
        'Xiaomi 14 256 Go',
        'OnePlus 12 256 Go',
        'Motorola Edge 50',
        'Honor Magic 6 Lite',
    ],
    'Ordinateur portable' => [
        'MacBook Air M2 13"',
        'MacBook Pro 14" M3',
        'Lenovo ThinkPad E14',
        'Lenovo Legion Y520',
        'HP Pavilion 15',
        'Dell Inspiron 15',
        'Asus Vivobook 16',
        'Acer Aspire 5',
        'MSI Modern 14',
    ],
    'Casque audio' => [
        'Sony WH-1000XM5',
        'Bose QuietComfort Ultra',
        'Apple AirPods Max',
        'JBL Tune 770NC',
        'Sennheiser Momentum 4',
        'Beats Studio Pro',
        'Sony WF-1000XM5',
        'AirPods Pro 2',
        'Marshall Major V',
    ],
    'Montre connectée' => [
        'Apple Watch Series 9',
        'Apple Watch SE 2',
        'Samsung Galaxy Watch 6',
        'Garmin Forerunner 265',
        'Garmin Venu 3',
        'Fitbit Versa 4',
        'Huawei Watch GT 4',
        'Amazfit GTR 4',
        'Withings ScanWatch 2',
    ],
    'Tablette' => [
        'iPad 10e génération',
        'iPad Air M2',
        'iPad Pro 11" M4',
        'Samsung Galaxy Tab S9',
        'Samsung Galaxy Tab A9+',
        'Lenovo Tab P12',
        'Xiaomi Pad 6',
        'Huawei MatePad 11',
        'Microsoft Surface Go 4',
    ],
    'Caméra' => [
        'Canon EOS R50',
        'Canon EOS R10',
        'Nikon Z30',
        'Nikon Z50',
        'Sony Alpha 6400',
        'Sony ZV-E10',
        'Fujifilm X-S20',
        'Panasonic Lumix G7',
        'GoPro HERO12 Black',
    ],
];

$prixParCategorie = [
    'Smartphone' => [199, 1299],
    'Ordinateur portable' => [499, 2499],
    'Casque audio' => [29, 399],
    'Montre connectée' => [99, 799],
    'Tablette' => [199, 1099],
    'Caméra' => [299, 2999],
];

$faker = Factory::create('fr_FR');

$stProduit = $pdo->prepare(
    'INSERT INTO produit (designation_p, date_in, stock_p, url_image_p, promo_p, categorie_id)
     VALUES (:designation, :date_in, :stock, NULL, :promo, :categorie_id)'
);
$stPrix = $pdo->prepare(
    'INSERT INTO prix_historique (p_id, prix_ht)
     VALUES (:p_id, :prix_ht)'
);

echo "Génération de {$nombre} produit(s) avec Faker...\n";

try {
    $pdo->beginTransaction();

    for ($i = 0; $i < $nombre; $i++) {
        $categorie = $faker->randomElement($categories);
        $libelle = (string) $categorie['libelle_c'];
        $modeles = $modelesParCategorie[$libelle] ?? ['Produit ' . $libelle];

        $designation = $faker->randomElement($modeles)
            . ' (réf. ' . $faker->unique()->bothify('??-####') . ')';

        [$prixMin, $prixMax] = $prixParCategorie[$libelle] ?? [10, 1000];
        $prixHt = round($faker->randomFloat(2, (float) $prixMin, (float) $prixMax), 2);

        $stock = $faker->numberBetween(0, 120);
        $promo = $faker->boolean(30) ? $faker->numberBetween(5, 30) : null;
        $dateIn = $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d');

        $stProduit->execute([
            'designation' => $designation,
            'date_in' => $dateIn,
            'stock' => $stock,
            'promo' => $promo,
            'categorie_id' => (int) $categorie['categorie_id'],
        ]);

        $stPrix->execute([
            'p_id' => (int) $pdo->lastInsertId(),
            'prix_ht' => $prixHt,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Génération annulée : " . $e->getMessage() . "\n");
    exit(1);
}

echo "{$nombre} produit(s) ajouté(s) avec succès.\n";
echo "Consultez le catalogue : http://localhost:8080/produits/catalogue.php\n";
