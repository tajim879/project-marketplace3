<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/withdrawals.php'));
}

$admin = require_admin();
csrf_require();

$action = (string)($_POST['action'] ?? '');
$wid = (int)($_POST['withdrawal_id'] ?? 0);

if ($wid <= 0) {
    flash('error', 'Invalid withdrawal request.');
    redirect(url('admin/withdrawals.php'));
}

if ($action === 'approve') {
    $res = WalletService::approveOrPay($wid, (int)$admin['id'], 'approved', '', (string)($_POST['admin_note'] ?? ''));
    flash($res['success'] ? 'info' : 'error', (string)$res['message']);
} elseif ($action === 'pay') {
    $ref = trim((string)($_POST['payment_reference'] ?? ''));
    $note = trim((string)($_POST['admin_note'] ?? ''));
    $res = WalletService::approveOrPay($wid, (int)$admin['id'], 'paid', $ref, $note);
    flash($res['success'] ? 'success' : 'error', (string)$res['message']);
} elseif ($action === 'reject') {
    $reason = trim((string)($_POST['rejection_reason'] ?? ''));
    $res = WalletService::reject($wid, (int)$admin['id'], $reason);
    flash($res['success'] ? 'info' : 'error', (string)$res['message']);
} else {
    flash('error', 'Unknown withdrawal action.');
}

redirect(url('admin/withdrawals.php'));
