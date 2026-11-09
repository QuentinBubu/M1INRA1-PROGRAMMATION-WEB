<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Site e-commerce de vente de produits de toute denrée">
  <meta name="author" content="Section Scratch M1 2026-2027 G-X">
  <meta name="keyword" content="e-commerce, vente, produits, denrée">
  <title><?= h($titre ?? 'E-Commerce') ?></title>
  <link rel="stylesheet" href="/assets/styles/style.css">
</head>
<body>

  <!-- Barre de navigation universelle -->
  <header class="navbar">
    <nav class="nav-container">
      <a href="/index.php" class="nav-brand">Boutique</a>

      <div class="nav-links">
        <a href="/index.php">Accueil</a>
        <a href="/produits/catalogue.php">Catalogue</a>
        <a href="/panier.php">Panier (<?= h((string) panier_quantite()) ?>)</a>

        <?php if (est_admin()): ?>
          <a href="/produits/liste.php">Gestion Produits</a>
          <a href="/categories/ajout.php">+ Catégorie</a>
        <?php endif; ?>

        <?php if (est_connecte()): ?>
          <a href="/profil.php" class="nav-user">Mon Profil</a>
          <form method="post" action="/deconnexion.php" class="nav-logout">
            <?= csrf_champ() ?>
            <button type="submit" class="btn-link">Déconnexion</button>
          </form>
        <?php else: ?>
          <a href="/connexion.php">Connexion</a>
          <a href="/inscription.php">Inscription</a>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <main>
    <h1><?= h($titre ?? '') ?></h1>
    <?php if ($m = flash_get()): ?><div class="info"><?= h($m) ?></div><?php endif; ?>
    <?php foreach (($erreurs ?? []) as $err): ?><div class="erreur"><?= h($err) ?></div><?php endforeach; ?>
