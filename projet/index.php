<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$titre = 'Accueil';
require __DIR__ . '/templates/header.php';
?>
<section class="accueil">
  <p>Bienvenue sur notre boutique en ligne.</p>
  <p>Découvrez notre catalogue de produits et trouvez ce dont vous avez besoin.</p>

  <nav aria-label="Navigation principale">
    <a class="bouton" href="produits/catalogue.php">Voir le catalogue</a>
    <a class="bouton secondaire" href="panier.php">Mon panier (<?= h((string) panier_quantite()) ?>)</a>

    <?php if (est_connecte()): ?>
      <a class="bouton secondaire" href="profil.php">Mon profil</a>
    <?php else: ?>
      <a class="bouton secondaire" href="connexion.php">Se connecter</a>
      <a class="bouton secondaire" href="inscription.php">Créer un compte</a>
    <?php endif; ?>
  </nav>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
