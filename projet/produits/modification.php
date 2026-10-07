<?php

require_once __DIR__ . '/../config.php';

if (!isset($_GET['id'])) {
    rediriger('/produits/liste.php');
}

$id = (int) $_GET['id'];

$produit = db()->prepare('SELECT * FROM produit WHERE p_id = :id');
$produit->execute(['id' => $id]);

if ($produit->rowCount() === 0) {
    rediriger('/produits/liste.php');
}

$produit = $produit->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = $_POST['designation'] ?? '';
    $stock = (int)($_POST['stock'] ?? 0);
    $date_in = $_POST['date_in'] ?? '';
    $image_url = $_POST['image_url'] ?? null;
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);

    $st = db()->prepare('UPDATE produit SET designation_p = :designation, stock_p = :stock, date_in = :date_in, url_image_p = :image_url, categorie_id = :categorie_id WHERE p_id = :id');
    
    $st->execute([
        'designation' => $designation,
        'stock' => $stock,
        'date_in' => $date_in,
        'image_url' => $image_url,
        'categorie_id' => $categorie_id,
        'id' => $id
    ]);

    $st = db()->prepare('INSERT INTO prix_historique (p_id, prix_ht) VALUES (:produit_id, :prix_ht)');
    $st->execute([
        'produit_id' => $id,
        'prix_ht' => (float)$_POST['prix']
    ]);

    rediriger('/produits/liste.php');
}

$categories = db()->query('SELECT * FROM categorie')->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../templates/header.php';

?>

<form method="post" novalidate>
  <label>Designation
    <input type="text" name="designation" required maxlength="255" value="<?= h($produit['designation_p']) ?>">
  </label>
  <label>Stock
    <input type="number" name="stock" required min="0" value="<?= h($produit['stock_p']) ?>">
  </label>
  <label>Date d'entrée
    <input type="date" name="date_in" required value="<?= h($produit['date_in']) ?>">
  </label>
  <label>Image URL
    <input type="url" name="image_url" value="<?= h($produit['url_image_p']) ?>">
  </label>

  <label>Prix
    <input type="number" name="prix" required min="0" step="0.01" value="<?= h($produit['prix_ht'] ?? '') ?>">
  </label>

    <label>Catégorie
        <select name="categorie_id" required>
            <?php foreach ($categories as $categorie): ?>
                <option value="<?= h($categorie['categorie_id']) ?>" <?= $categorie['categorie_id'] == $produit['categorie_id'] ? 'selected' : '' ?>>
                    <?= h($categorie['libelle_c']) ?>
                </option>
            <?php endforeach; ?>
        </select>

  <button type="submit">Modifier</button>

</form>

<a href="../categories/ajout.php">Ajouter une catégorie</a>

<?php require __DIR__ . '/../templates/footer.php'; ?>
