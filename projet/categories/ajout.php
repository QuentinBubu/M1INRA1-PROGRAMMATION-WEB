<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$erreurs = [];
$donnees = ['libelle' => '', 'taxe' => '20.00'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        $libelle = trim((string) ($_POST['libelle_c'] ?? ''));
        $taxe = filter_var(
            $_POST['taxe_c'] ?? null,
            FILTER_VALIDATE_FLOAT,
            ['options' => ['min_range' => 0, 'max_range' => 100]]
        );

        $donnees = [
            'libelle' => $libelle,
            'taxe' => (string) ($_POST['taxe_c'] ?? ''),
        ];

        if ($libelle === '' || mb_strlen($libelle) > 100) {
            $erreurs[] = 'Le libellé est obligatoire (100 caractères maximum).';
        }
        if ($taxe === false) {
            $erreurs[] = 'La taxe doit être un pourcentage entre 0 et 100.';
        }

        if ($erreurs === []) {
            try {
                $st = db()->prepare('INSERT INTO categorie (libelle_c, taxe_c) VALUES (:libelle, :taxe)');
                $st->execute([
                    'libelle' => $libelle,
                    'taxe' => round($taxe / 100, 4),
                ]);

                flash_set('Catégorie ajoutée avec succès.');
                rediriger('/categories/ajout.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $erreurs[] = 'Une catégorie porte déjà ce libellé.';
                } else {
                    error_log('Ajout catégorie : ' . $e->getMessage());
                    $erreurs[] = 'Impossible d\'ajouter la catégorie.';
                }
            }
        }
    }
}

// Liste des catégories existantes et de leur taux de TVA.
$categories = db()->query(
    'SELECT categorie_id, libelle_c, taxe_c
     FROM categorie
     ORDER BY libelle_c'
)->fetchAll();

$titre = 'Ajout de catégorie';
require __DIR__ . '/../templates/header.php';
?>

<form method="post">
  <?= csrf_champ() ?>

  <label>Libellé
    <input type="text" name="libelle_c" required maxlength="100"
           value="<?= h((string) $donnees['libelle']) ?>">
  </label>

  <label>Taxe (%)
    <input type="number" name="taxe_c" required min="0" max="100" step="0.01"
           value="<?= h((string) $donnees['taxe']) ?>">
  </label>

  <button type="submit">Ajouter</button>
</form>

<h2>TVA déjà en place</h2>

<?php if ($categories === []): ?>
  <p>Aucune catégorie enregistrée.</p>
<?php else: ?>
  <div class="table-wrapper">
    <table class="table-admin">
      <thead>
        <tr>
          <th scope="col">Catégorie</th>
          <th scope="col">TVA</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $categorie): ?>
          <tr>
            <td><?= h($categorie['libelle_c']) ?></td>
            <td><?= h(number_format((float) $categorie['taxe_c'] * 100, 2, ',', ' ')) ?> %</td>
            <td class="actions">
              <a href="modification.php?id=<?= h((string) $categorie['categorie_id']) ?>">Modifier</a>
              <form method="post" action="suppression.php" data-confirm="Supprimer cette catégorie et sa TVA ?">
                <?= csrf_champ() ?>
                <input type="hidden" name="id" value="<?= h((string) $categorie['categorie_id']) ?>">
                <button type="submit">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<p><a href="../produits/liste.php">Retour à la gestion des produits</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
