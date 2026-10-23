<?php
$page = 'Coffee';
require __DIR__ . '/inc/header.php';
$catalog = [
  'ETH-01' => ['Yirgacheffe Washed',  'Ethiopia',  'Jasmine, lemon, black tea', 14.50],
  'COL-02' => ['Huila Natural',       'Colombia',  'Red fruit, panela, cocoa',  13.00],
  'GUA-03' => ['Antigua Honey',       'Guatemala', 'Toffee, orange, almond',    12.50],
  'BLD-04' => ['Dockside Espresso',   'Blend',     'Dark chocolate, hazelnut',  11.50],
];
if (isset($_GET['add'], $catalog[$_GET['add']])) {
    $_SESSION['cart'][$_GET['add']] = ($_SESSION['cart'][$_GET['add']] ?? 0) + 1;
}
$count = array_sum($_SESSION['cart'] ?? []);
?>
<h1>This week's roast</h1>
<p class="muted">Cart: <?= $count ?> bag(s)</p>
<div class="cards">
<?php foreach ($catalog as $sku => [$name, $origin, $notes, $price]): ?>
  <article>
    <h2><?= htmlspecialchars($name) ?></h2>
    <p class="muted"><?= $origin ?> &middot; 250 g</p>
    <p><?= $notes ?></p>
    <p><strong>&euro;<?= number_format($price, 2) ?></strong>
       <a class="btn small" href="?add=<?= $sku ?>">Add to cart</a></p>
  </article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
