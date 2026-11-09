<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    flash_set('Catégorie introuvable.');
    rediriger('/categories/ajout.php');
}

$st = db()->prepare('SELECT categorie_id, libelle_c, taxe_c FROM categorie WHERE categorie_id = :id LIMIT 1');
$st->execute(['id' => $id]);
$categorie = $st->fetch();

if ($categorie === false) {
    flash_set('Catégorie introuvable.');
    rediriger('/categories/ajout.php');
}

$erreurs = [];
$donnees = [
    'taxe' => number_format((float) $categorie['taxe_c'] * 100, 2, '.', ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        $taxe = filter_var(
            $_POST['taxe_c'] ?? null,
            FILTER_VALIDATE_FLOAT,
            ['options' => ['min_range' => 0, 'max_range' => 100]]
        );
        $donnees['taxe'] = trim((string) ($_POST['taxe_c'] ?? ''));

        if ($taxe === false) {
            $erreurs[] = 'La taxe doit être un pourcentage entre 0 et 100.';
        }

        if ($erreurs === []) {
            $st = db()->prepare('UPDATE categorie SET taxe_c = :taxe WHERE categorie_id = :id');
            $st->execute([
                'taxe' => round($taxe / 100, 4),
                'id' => $id,
            ]);

            flash_set('TVA modifiée avec succès.');
            rediriger('/categories/ajout.php');
        }
    }
}

$titre = 'Modification de la TVA';
require __DIR__ . '/../templates/header.php';
?>

<p><strong>Catégorie :</strong> <?= h($categorie['libelle_c']) ?></p>

<form method="post">
  <?= csrf_champ() ?>

  <label>TVA (%)
    <input type="number" name="taxe_c" required min="0" max="100" step="0.01"
           value="<?= h((string) $donnees['taxe']) ?>">
  </label>

  <button type="submit">Enregistrer la TVA</button>
</form>

<p><a href="ajout.php">Retour aux catégories</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
