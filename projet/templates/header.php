<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titre ?? 'Compte') ?></title>
  <link rel="stylesheet" href="/assets/styles/style.css">
  <meta name="description" content="Site e-commerce de vente de produits de toute denrée">
  <meta name="author" content="Section Scratch M1 2026-2027 G-X">
  <meta name="keyword" content="e-commerce, vente, produits, denrée">
</head>

<body>
  <main>
    <h1><?= h($titre ?? '') ?></h1>
    <?php if ($m = flash_get()): ?><div class="info"><?= h($m) ?></div><?php endif; ?>
    <?php foreach (($erreurs ?? []) as $err): ?><div class="erreur"><?= h($err) ?></div><?php endforeach; ?>