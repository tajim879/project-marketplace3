<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('checkout.php'));
}

$user = require_login();
csrf_require();

$notes = trim((string)($_POST['order_notes'] ?? '')) ?: null;
$preferredMethod = strtolower(trim((string)($_POST['preferred_method'] ?? '')));

$res = OrderService::createOrderFromCart((int)$user['id'], $notes);
if (!$res['success']) {
    flash('error', (string)$res['message']);
    redirect(url('checkout.php'));
}

if (($res['status'] ?? '') === 'paid') {
    flash('success', "Order {$res['order_number']} is active! Your licenses are ready to download.");
    redirect(url('order-success.php?order=' . urlencode((string)$res['order_number'])));
}

$qs = 'order=' . urlencode((string)$res['order_number']);
if (in_array($preferredMethod, ['bkash', 'nagad', 'rocket'], true)) {
    $qs .= '&method=' . urlencode($preferredMethod);
}
redirect(url('payment.php?' . $qs));
