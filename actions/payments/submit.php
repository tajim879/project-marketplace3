<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('buyer/orders.php'));
}

$user = require_login();
csrf_require();

$orderNumber = trim((string)($_POST['order_number'] ?? ''));
$methodId = (int)($_POST['payment_method_id'] ?? 0);
$sender = trim((string)($_POST['sender_number'] ?? ''));
$trxId = trim((string)($_POST['transaction_id'] ?? ''));
$amount = trim((string)($_POST['paid_amount'] ?? ''));
$note = trim((string)($_POST['buyer_note'] ?? '')) ?: null;

$res = OrderService::submitManualPayment(
    (int)$user['id'],
    $orderNumber,
    $methodId,
    $sender,
    $trxId,
    $amount,
    $note,
    $_FILES['payment_proof'] ?? []
);

if (!$res['success']) {
    set_old_input($_POST);
    flash('error', (string)$res['message']);
    redirect(url('payment.php?order=' . urlencode($orderNumber)));
}

flash('success', 'Payment submitted! Our verification team will review your transaction and unlock your download.');
redirect(url('order-success.php?order=' . urlencode((string)$res['order_number'])));
