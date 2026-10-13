<?php

require_once __DIR__ . '/../config.php';

$titre = 'Ajout de catégorie';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = $_POST['libelle_c'] ?? '';
    $taxe = $_POST['taxe_c'] ?? 0;
    $st = db()->prepare('INSERT INTO categorie (libelle_c, taxe_c) VALUES (:libelle_c, :taxe_c)');
    $st->execute(['libelle_c' => $libelle, 'taxe_c' => $taxe / 100]);

    rediriger('/categories/liste.php');
}

require __DIR__ . '/../templates/header.php';
?>
<form method="post" novalidate>
  <label>Libellé
    <input type="text" name="libelle_c" required maxlength="100">
  </label>
  <label>Taxe
    <input type="number" name="taxe_c" required min="0" max="100" value="20.00">
  </label>
  <button type="submit">Ajouter</button>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
