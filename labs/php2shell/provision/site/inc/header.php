<?php
// FICTIONAL company "Harborline Coffee Roasters" - demo content for a classroom lab. Not a real business.
session_start();
$page = $page ?? 'Home';
$nav = ['index.php' => 'Home', 'about.php' => 'About', 'products.php' => 'Coffee', 'contact.php' => 'Contact'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page) ?> | Harborline Coffee Roasters</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<header>
  <div class="wrap">
    <a class="logo" href="/">Harborline <span>Coffee Roasters</span></a>
    <nav>
      <?php foreach ($nav as $href => $label): ?>
        <a href="/<?= $href ?>"<?= $label === $page ? ' class="on"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<main class="wrap">
