<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/payments.php'));
}

$admin = require_admin();
csrf_require();

$action = (string)($_POST['action'] ?? '');
$paymentId = (int)($_POST['payment_id'] ?? 0);
$orderId = (int)($_POST['order_id'] ?? 0);
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$fallback = $returnTo !== '' && str_starts_with($returnTo, 'admin/') ? url($returnTo) : url('admin/payments.php');

if ($action === 'under_verification' && $paymentId > 0) {
    $res = OrderService::markPaymentUnderVerification($paymentId, (int)$admin['id']);
    flash($res['success'] ? 'info' : 'error', (string)$res['message']);
    redirect($fallback);
}

if ($action === 'approve' && $paymentId > 0) {
    $note = trim((string)($_POST['admin_note'] ?? ''));
    $res = OrderService::approvePayment($paymentId, (int)$admin['id'], $note);
    flash($res['success'] ? 'success' : 'error', (string)$res['message']);
    redirect($fallback);
}

if ($action === 'reject' && $paymentId > 0) {
    $reason = trim((string)($_POST['rejection_reason'] ?? ''));
    $res = OrderService::rejectPayment($paymentId, (int)$admin['id'], $reason);
    flash($res['success'] ? 'info' : 'error', (string)$res['message']);
    redirect($fallback);
}

if ($action === 'cancel_or_refund' && $orderId > 0) {
    $reason = trim((string)($_POST['reason'] ?? ''));
    $res = OrderService::cancelOrRefundOrder($orderId, (int)$admin['id'], $reason);
    flash($res['success'] ? 'success' : 'error', (string)$res['message']);
    redirect($returnTo !== '' && str_starts_with($returnTo, 'admin/') ? url($returnTo) : url('admin/orders.php'));
}

flash('error', 'Unknown payment/order action.');
redirect($fallback);
