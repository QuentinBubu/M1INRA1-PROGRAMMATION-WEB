<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
exiger_admin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    rediriger('/produits/liste.php');
}

$st = db()->prepare('SELECT * FROM produit WHERE p_id = :id AND is_deleted = 0 LIMIT 1');
$st->execute(['id' => $id]);
$produit = $st->fetch();

if ($produit === false) {
    flash_set('Produit introuvable.');
    rediriger('/produits/liste.php');
}

$stPrix = db()->prepare(
    'SELECT prix_ht
     FROM prix_historique
     WHERE p_id = :id
     ORDER BY date_debut DESC, prix_id DESC
     LIMIT 1'
);
$stPrix->execute(['id' => $id]);
$prixActuel = $stPrix->fetchColumn();
$prixActuel = $prixActuel !== false ? (float) $prixActuel : null;

$erreurs = [];
$categories = db()->query('SELECT categorie_id, libelle_c FROM categorie ORDER BY libelle_c')->fetchAll();

$donnees = [
    'designation'  => $produit['designation_p'],
    'stock'        => (int) $produit['stock_p'],
    'date_in'      => $produit['date_in'],
    'prix'         => $prixActuel ?? '',
    'categorie_id' => (int) $produit['categorie_id'],
    'image_url'    => (string) ($produit['url_image_p'] ?? ''),
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
                    'UPDATE produit
                     SET designation_p = :designation,
                         stock_p = :stock,
                         date_in = :date_in,
                         url_image_p = :image_url,
                         categorie_id = :categorie_id
                     WHERE p_id = :id AND is_deleted = 0'
                );
                $st->execute([
                    'designation'  => $donnees['designation'],
                    'stock'        => $donnees['stock'],
                    'date_in'      => $donnees['date_in'],
                    'image_url'    => $donnees['image_url'] !== '' ? $donnees['image_url'] : null,
                    'categorie_id' => $donnees['categorie_id'],
                    'id'           => $id,
                ]);

                // On n'ajoute une ligne d'historique que si le prix a changé.
                if ($prixActuel === null || abs($prixActuel - (float) $donnees['prix']) > 0.0001) {
                    $stPrix = $pdo->prepare('INSERT INTO prix_historique (p_id, prix_ht) VALUES (:p_id, :prix_ht)');
                    $stPrix->execute(['p_id' => $id, 'prix_ht' => $donnees['prix']]);
                }

                $pdo->commit();
                flash_set('Produit modifié avec succès.');
                rediriger('/produits/liste.php');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Modification produit : ' . $e->getMessage());
                $erreurs[] = 'Impossible de modifier le produit. Vérifiez les données saisies.';
            }
        }
    }
}

$titre = 'Modification de produit';
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

  <button type="submit">Enregistrer les modifications</button>
</form>

<form method="post" action="suppression.php" data-confirm="Supprimer ce produit ?">
  <?= csrf_champ() ?>
  <input type="hidden" name="id" value="<?= h((string) $id) ?>">
  <button type="submit">Supprimer ce produit</button>
</form>

<p><a href="liste.php">Retour à la gestion des produits</a></p>

<?php require __DIR__ . '/../templates/footer.php'; ?>
