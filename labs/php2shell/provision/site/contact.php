<?php
$page = 'Contact';
require __DIR__ . '/inc/header.php';
$msg = null;
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Invalid form token, please reload the page.';
    } elseif (trim($_POST['name'] ?? '') === '' || trim($_POST['message'] ?? '') === '') {
        $msg = 'Please fill in your name and a message.';
    } else {
        $att = $_FILES['attachment']['name'] ?? '';
        $msg = 'Thanks ' . htmlspecialchars($_POST['name']) . ', we will reply within one working day.'
             . ($att !== '' ? ' (attachment "' . htmlspecialchars($att) . '" received)' : '');
        // Demo: the message is not stored and the attachment is discarded.
    }
}
?>
<h1>Contact us</h1>
<?php if ($msg): ?><p class="notice"><?= $msg ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
  <label>Name <input name="name" required></label>
  <label>Email <input type="email" name="email"></label>
  <label>Message <textarea name="message" rows="5" required></textarea></label>
  <label>Attach a file (wholesale price list, order sheet...) <input type="file" name="attachment"></label>
  <button class="btn" type="submit">Send</button>
</form>
<?php require __DIR__ . '/inc/footer.php'; ?>
