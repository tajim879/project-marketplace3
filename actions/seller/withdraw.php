<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/withdrawals.php'));
}

$user = require_seller();
csrf_require();

$res = WalletService::requestWithdrawal(
    (int)$user['id'],
    (string)($_POST['method'] ?? ''),
    (string)($_POST['account_number'] ?? ''),
    (string)($_POST['amount'] ?? '')
);

if ($res['success']) {
    flash('success', (string)$res['message']);
} else {
    set_old_input($_POST);
    flash('error', (string)$res['message']);
}

redirect(url('seller/withdrawals.php'));
