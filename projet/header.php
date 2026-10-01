<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titre ?? 'Compte') ?></title>
  <link rel="stylesheet" href="./assets/styles/style.css">
</head>

<body>
  <main>
    <h1><?= h($titre ?? '') ?></h1>
    <?php if ($m = flash_get()): ?><div class="info"><?= h($m) ?></div><?php endif; ?>
    <?php foreach (($erreurs ?? []) as $err): ?><div class="erreur"><?= h($err) ?></div><?php endforeach; ?>