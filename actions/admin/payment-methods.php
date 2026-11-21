<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/payment-methods.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$id = (int)($_POST['id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$accountNumber = trim((string)($_POST['account_number'] ?? ''));
$accountType = (string)($_POST['account_type'] ?? 'personal');
$instructions = trim((string)($_POST['instructions'] ?? ''));
$sortOrder = (int)($_POST['sort_order'] ?? 1);
$isEnabled = !empty($_POST['is_enabled']) ? 1 : 0;

if (!in_array($accountType, ['personal', 'agent', 'merchant'], true)) {
    $accountType = 'personal';
}

if ($id <= 0 || $name === '' || $accountNumber === '' || $instructions === '') {
    flash('error', 'Please complete wallet name, receiving account number, and step-by-step instructions.');
    redirect(url('admin/payment-methods.php'));
}

$pdo->prepare(
    "UPDATE `payment_methods`
     SET `name` = ?, `account_number` = ?, `account_type` = ?, `instructions` = ?,
         `sort_order` = ?, `is_enabled` = ?, `updated_at` = ?
     WHERE `id` = ?"
)->execute([
    mb_substr($name, 0, 80),
    mb_substr($accountNumber, 0, 30),
    $accountType,
    mb_substr($instructions, 0, 2000),
    $sortOrder,
    $isEnabled,
    date('Y-m-d H:i:s'),
    $id,
]);

log_admin_action((int)$admin['id'], 'update_payment_method', 'payment_method', $id, "Updated {$name} ({$accountNumber}, enabled={$isEnabled}).");
flash('success', "{$name} payment method updated successfully.");
redirect(url('admin/payment-methods.php'));
