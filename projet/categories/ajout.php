<?php

require_once __DIR__ . '/../config.php';

$titre = 'Ajout de catégorie';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = $_POST['libelle_c'] ?? '';
    $st = db()->prepare('INSERT INTO categorie (libelle_c) VALUES (:libelle_c)');
    $st->execute(['libelle_c' => $libelle]);

    rediriger('/categories/liste.php');
}

require __DIR__ . '/../templates/header.php';
?>
<form method="post" novalidate>
  <label>Libellé
    <input type="text" name="libelle_c" required maxlength="100">
  </label>
  <button type="submit">Ajouter</button>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
