<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/coupons.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$now = date('Y-m-d H:i:s');

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string)($_POST['code'] ?? ''))));
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 120);
    $type = in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed'], true) ? (string)$_POST['discount_type'] : 'percentage';
    $val = (float)($_POST['discount_value'] ?? 0);
    $minOrder = max(0, (float)($_POST['min_order_amount'] ?? 0));
    $maxDiscRaw = trim((string)($_POST['max_discount_amount'] ?? ''));
    $maxDisc = $maxDiscRaw !== '' && (float)$maxDiscRaw > 0 ? number_format((float)$maxDiscRaw, 2, '.', '') : null;
    $limitRaw = trim((string)($_POST['usage_limit'] ?? ''));
    $limit = $limitRaw !== '' && (int)$limitRaw > 0 ? (int)$limitRaw : null;
    $startsRaw = trim((string)($_POST['starts_at'] ?? ''));
    $starts = $startsRaw !== '' ? date('Y-m-d H:i:s', strtotime($startsRaw)) : $now;
    $expiresRaw = trim((string)($_POST['expires_at'] ?? ''));
    $expires = $expiresRaw !== '' ? date('Y-m-d 23:59:59', strtotime($expiresRaw)) : date('Y-m-d 23:59:59', strtotime('+1 year'));
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string)$_POST['status'] : 'active';

    if ($code === '' || $title === '' || $val <= 0 || ($type === 'percentage' && $val > 95)) {
        flash('error', 'Please enter a valid coupon code, title, and discount value.');
        redirect(url('admin/coupons.php'));
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                "UPDATE `coupons`
                 SET `code` = ?, `title` = ?, `discount_type` = ?, `discount_value` = ?,
                     `min_order_amount` = ?, `max_discount_amount` = ?, `usage_limit` = ?,
                     `starts_at` = ?, `expires_at` = ?, `status` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([$code, $title, $type, number_format($val, 2, '.', ''), number_format($minOrder, 2, '.', ''), $maxDisc, $limit, $starts, $expires, $status, $now, $id]);
            log_admin_action($aid, 'update_coupon', 'coupon', $id, "Updated coupon {$code}.");
            flash('success', "Coupon {$code} updated.");
        } else {
            $pdo->prepare(
                "INSERT INTO `coupons`
                 (`code`, `title`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`,
                  `usage_limit`, `used_count`, `starts_at`, `expires_at`, `status`, `created_by`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)"
            )->execute([$code, $title, $type, number_format($val, 2, '.', ''), number_format($minOrder, 2, '.', ''), $maxDisc, $limit, $starts, $expires, $status, $aid, $now, $now]);
            log_admin_action($aid, 'create_coupon', 'coupon', (int)$pdo->lastInsertId(), "Created coupon {$code}.");
            flash('success', "Coupon {$code} created.");
        }
    } catch (Throwable $e) {
        flash('error', 'Coupon code already exists.');
    }
    redirect(url('admin/coupons.php'));
}

if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE `coupons` SET `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END, `updated_at` = ? WHERE `id` = ?")
        ->execute([$now, $id]);
    log_admin_action($aid, 'toggle_coupon', 'coupon', $id, 'Toggled coupon status.');
    flash('info', 'Coupon status toggled.');
    redirect(url('admin/coupons.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `coupons` WHERE `id` = ?")->execute([$id]);
    log_admin_action($aid, 'delete_coupon', 'coupon', $id, 'Deleted coupon.');
    flash('info', 'Coupon deleted.');
    redirect(url('admin/coupons.php'));
}

redirect(url('admin/coupons.php'));
