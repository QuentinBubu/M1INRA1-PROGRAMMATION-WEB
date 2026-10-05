<?php
require_once __DIR__ . '/../config.php';

$titre = 'Ajout de produit';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = $_POST['designation'] ?? '';
    $stock = (int)($_POST['stock'] ?? 0);
    $date_in = $_POST['date_in'] ?? '';
    $image_url = $_POST['image_url'] ?? null;
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);

    $st = db()->prepare('INSERT INTO produit (designation_p, stock_p, date_in, url_image_p, categorie_id)
                         VALUES (:designation, :stock, :date_in, :image_url, :categorie_id)
    ');
    
    $st->execute([
        'designation' => $designation,
        'stock' => $stock,
        'date_in' => $date_in,
        'image_url' => $image_url,
        'categorie_id' => $categorie_id
    ]);

    rediriger('/produits/liste.php');
}

$categories = db()->query('SELECT * FROM categorie')->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../templates/header.php';
?>
<form method="post" novalidate>
  <label>Designation
    <input type="text" name="designation" required maxlength="255">
  </label>
  <label>Stock
    <input type="number" name="stock" required min="0">
  </label>
  <label>Date d'entrée
    <input type="date" name="date_in" required>
  </label>
  <label>Image URL
    <input type="url" name="image_url">
  </label>

    <label>Catégorie
        <select name="categorie_id" required>
            <?php foreach ($categories as $categorie): ?>
                <option value="<?= h($categorie['categorie_id']) ?>">
                    <?= h($categorie['libelle_c']) ?>
                </option>
            <?php endforeach; ?>
        </select>

  <button type="submit">Ajouter</button>
</form>

<a href="../categories/ajout.php">Ajouter une catégorie</a>

<?php require __DIR__ . '/../templates/footer.php'; ?>
