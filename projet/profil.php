<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
exiger_connexion();

// On relit l'utilisateur en base (au cas où il aurait été supprimé)
$st = db()->prepare('SELECT user_login, user_mail, user_date_new, user_date_login FROM `user` WHERE user_id = :id');
$st->execute(['id' => $_SESSION['user_id']]);
$u = $st->fetch();

if (!$u) {
    deconnecter();
    rediriger('connexion.php');
}

$titre = 'Mon profil';
require __DIR__ . '/header.php';
?>
<p>Bonjour <strong><?= h($u['user_login']) ?></strong> !</p>
<ul>
  <li>E-mail : <?= h($u['user_mail']) ?></li>
  <li>Inscrit le : <?= h($u['user_date_new']) ?></li>
  <li>Dernière connexion : <?= h($u['user_date_login']) ?></li>
</ul>
<form method="post" action="deconnexion.php">
  <?= csrf_champ() ?>
  <button type="submit">Se déconnecter</button>
</form>
<?php require __DIR__ . '/footer.php'; ?>
