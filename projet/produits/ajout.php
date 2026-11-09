<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$erreurs = [];
$categories = db()->query('SELECT categorie_id, libelle_c FROM categorie ORDER BY libelle_c')->fetchAll();

$donnees = [
    'designation'  => '',
    'stock'        => 0,
    'date_in'      => date('Y-m-d'),
    'prix'         => '',
    'categorie_id' => $categories[0]['categorie_id'] ?? 0,
    'image_url'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide()) {
        $erreurs[] = 'La requête a expiré, veuillez réessayer.';
    } else {
        [$donnees, $erreurs] = valider_produit($_POST);
        $donnees['image_url'] = trim((string) ($_POST['image_url'] ?? ''));

        if (!url_image_valide($donnees['image_url'])) {
            $erreurs[] = 'L\'URL de l\'image est invalide (URL http(s) ou chemin local attendu).';
        }

        if ($erreurs === []) {
            $pdo = db();
            try {
                $pdo->beginTransaction();

                $st = $pdo->prepare(
                    'INSERT INTO produit (designation_p, stock_p, date_in, url_image_p, categorie_id)
                     VALUES (:designation, :stock, :date_in, :image_url, :categorie_id)'
                );
                $st->execute([
                    'designation'  => $donnees['designation'],
                    'stock'        => $donnees['stock'],
                    'date_in'      => $donnees['date_in'],
                    'image_url'    => $donnees['image_url'] !== '' ? $donnees['image_url'] : null,
                    'categorie_id' => $donnees['categorie_id'],
                ]);
                $produitId = (int) $pdo->lastInsertId();

                $stPrix = $pdo->prepare('INSERT INTO prix_historique (p_id, prix_ht) VALUES (:p_id, :prix_ht)');
                $stPrix->execute(['p_id' => $produitId, 'prix_ht' => $donnees['prix']]);

                $pdo->commit();
                flash_set('Produit ajouté avec succès.');
                rediriger('/produits/liste.php');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Ajout produit : ' . $e->getMessage());
                $erreurs[] = 'Impossible d\'ajouter le produit. Vérifiez les données saisies.';
            }
        }
    }
}

$titre = 'Ajout de produit';
require __DIR__ . '/../templates/header.php';
?>

<form method="post">
  <?= csrf_champ() ?>

  <label>Désignation
    <input type="text" name="designation" required maxlength="255"
           value="<?= h((string) $donnees['designation']) ?>">
  </label>

  <label>Stock
    <input type="number" name="stock" required min="0" step="1"
           value="<?= h((string) $donnees['stock']) ?>">
  </label>

  <label>Date d'entrée
    <input type="date" name="date_in" required value="<?= h((string) $donnees['date_in']) ?>">
  </label>

  <label>Prix HT (€)
    <input type="number" name="prix" required min="0" step="0.01"
           value="<?= h((string) $donnees['prix']) ?>">
  </label>

  <label>Catégorie
    <select name="categorie_id" required>
      <?php foreach ($categories as $categorie): ?>
        <option value="<?= h((string) $categorie['categorie_id']) ?>"
          <?= (int) $categorie['categorie_id'] === (int) $donnees['categorie_id'] ? 'selected' : '' ?>>
          <?= h($categorie['libelle_c']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>URL / chemin de l'image (optionnel)
    <input type="text" name="image_url" maxlength="2048"
           placeholder="https://exemple.fr/image.jpg"
           value="<?= h((string) $donnees['image_url']) ?>">
  </label>

  <button type="submit">Ajouter</button>
</form>

<p><a href="../categories/ajout.php">Ajouter une catégorie</a></p>
<p><a href="liste.php">Retour à la gestion des produits</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
