<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/users.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$targetId = (int)($_POST['user_id'] ?? 0);
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$back = ($returnTo !== '' && str_starts_with($returnTo, 'admin/')) ? url($returnTo) : url('admin/users.php');
$now = date('Y-m-d H:i:s');

if ($action === 'create_user') {
    $v = (new Validator($_POST))
        ->required('full_name', 'Full name')->minLength('full_name', 2, 'Full name')
        ->required('username', 'Username')->username('username', 'Username')
        ->required('email', 'Email')->email('email', 'Email')
        ->required('password', 'Password')->passwordStrength('password', 'Password')
        ->inList('role', ['buyer', 'seller', 'admin'], 'Role');

    if ($v->fails()) {
        flash('error', $v->firstError());
        redirect($back);
    }

    $username = strtolower(trim((string)$_POST['username']));
    $email = strtolower(trim((string)$_POST['email']));
    $role = (string)($_POST['role'] ?? 'buyer');
    $isSeller = in_array($role, ['seller', 'admin'], true) ? 1 : 0;
    $sellerStatus = $isSeller ? 'approved' : 'none';

    $dupStmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `email` = ? OR `username` = ? LIMIT 1");
    $dupStmt->execute([$email, $username]);
    if ($dupStmt->fetch()) {
        flash('error', 'Email or username is already registered.');
        redirect($back);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO `users`
             (`full_name`, `username`, `email`, `phone`, `password_hash`, `role`, `is_seller`, `seller_status`, `is_verified_seller`, `status`, `email_verified_at`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)"
        )->execute([
            trim((string)$_POST['full_name']),
            $username,
            $email,
            trim((string)($_POST['phone'] ?? '')) ?: null,
            password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
            $role,
            $isSeller,
            $sellerStatus,
            $isSeller,
            $now,
            $now,
            $now,
        ]);
        $newUid = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO `user_profiles` (`user_id`, `store_name`, `store_slug`, `seller_approved_at`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $newUid,
            $isSeller ? (trim((string)$_POST['full_name']) . ' Studio') : null,
            $isSeller ? $username : null,
            $isSeller ? $now : null,
            $now,
            $now,
        ]);

        if ($isSeller) {
            $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`, `updated_at`) VALUES (?, ?)")->execute([$newUid, $now]);
        }

        $pdo->commit();
        log_admin_action($aid, 'create_user', 'user', $newUid, "Created {$role} account @{$username}.");
        flash('success', "Account @{$username} ({$role}) created successfully.");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not create user account.');
    }
    redirect($back);
}

if ($targetId <= 0) {
    flash('error', 'Invalid user ID.');
    redirect($back);
}

$uStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
$uStmt->execute([$targetId]);
$target = $uStmt->fetch();
if (!$target) {
    flash('error', 'User not found.');
    redirect($back);
}

if ($targetId === $aid && in_array($action, ['update_status', 'update_role'], true)) {
    flash('error', 'You cannot demote or suspend your own active admin account.');
    redirect($back);
}

if ($action === 'update_status') {
    $st = (string)($_POST['status'] ?? 'active');
    if (in_array($st, ['active', 'suspended', 'blocked'], true)) {
        $pdo->prepare("UPDATE `users` SET `status` = ?, `updated_at` = ? WHERE `id` = ?")->execute([$st, $now, $targetId]);
        if ($st !== 'active') {
            $pdo->prepare("UPDATE `user_sessions` SET `status` = 'revoked' WHERE `user_id` = ?")->execute([$targetId]);
        }
        log_admin_action($aid, 'update_user_status', 'user', $targetId, "Changed @{$target['username']} status to {$st}.");
        flash('success', "User @{$target['username']} status updated to {$st}.");
    }
    redirect($back);
}

if ($action === 'update_role') {
    $role = (string)($_POST['role'] ?? 'buyer');
    if (in_array($role, ['buyer', 'seller', 'admin'], true)) {
        $isSeller = in_array($role, ['seller', 'admin'], true);
        if ($isSeller) {
            $pdo->prepare("UPDATE `users` SET `role` = ?, `is_seller` = 1, `seller_status` = 'approved', `updated_at` = ? WHERE `id` = ?")
                ->execute([$role, $now, $targetId]);
            $pdo->prepare(
                "UPDATE `user_profiles`
                 SET `store_name` = COALESCE(`store_name`, ?),
                     `store_slug` = COALESCE(`store_slug`, ?),
                     `seller_approved_at` = COALESCE(`seller_approved_at`, ?),
                     `updated_at` = ?
                 WHERE `user_id` = ?"
            )->execute([$target['full_name'] . ' Studio', $target['username'], $now, $now, $targetId]);
            ensure_seller_balance($targetId);
        } else {
            $pdo->prepare("UPDATE `users` SET `role` = 'buyer', `updated_at` = ? WHERE `id` = ?")->execute([$now, $targetId]);
        }
        log_admin_action($aid, 'update_user_role', 'user', $targetId, "Changed @{$target['username']} role to {$role}.");
        flash('success', "User @{$target['username']} role changed to {$role}.");
    }
    redirect($back);
}

if ($action === 'reset_password') {
    $newPw = (string)($_POST['new_password'] ?? '');
    if (strlen($newPw) < 8) {
        flash('error', 'New password must be at least 8 characters.');
        redirect($back);
    }
    $pdo->prepare("UPDATE `users` SET `password_hash` = ?, `updated_at` = ? WHERE `id` = ?")
        ->execute([password_hash($newPw, PASSWORD_DEFAULT), $now, $targetId]);
    log_admin_action($aid, 'reset_user_password', 'user', $targetId, "Reset password for @{$target['username']}.");
    flash('success', "Password reset for @{$target['username']}.");
    redirect($back);
}

if ($action === 'seller_decision') {
    $dec = (string)($_POST['seller_status'] ?? '');
    $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 300) ?: null;

    if ($dec === 'approved') {
        $newRole = $target['role'] === 'admin' ? 'admin' : 'seller';
        $pdo->prepare("UPDATE `users` SET `role` = ?, `is_seller` = 1, `seller_status` = 'approved', `is_verified_seller` = 1, `updated_at` = ? WHERE `id` = ?")
            ->execute([$newRole, $now, $targetId]);
        $pdo->prepare("UPDATE `user_profiles` SET `seller_approved_at` = ?, `seller_rejection_reason` = NULL, `updated_at` = ? WHERE `user_id` = ?")
            ->execute([$now, $now, $targetId]);
        ensure_seller_balance($targetId);
        NotificationService::send($targetId, 'seller_approved', 'Seller account approved!', 'Your Seller Studio is active — start uploading projects now.', 'seller/dashboard.php', 'store');
        log_admin_action($aid, 'approve_seller', 'user', $targetId, "Approved & verified seller @{$target['username']}.");
        flash('success', "Seller @{$target['username']} approved and verified.");
    } elseif (in_array($dec, ['rejected', 'suspended'], true)) {
        $newRole = $target['role'] === 'admin' ? 'admin' : 'buyer';
        $pdo->prepare("UPDATE `users` SET `role` = ?, `is_seller` = 0, `seller_status` = ?, `updated_at` = ? WHERE `id` = ?")
            ->execute([$newRole, $dec, $now, $targetId]);
        $pdo->prepare("UPDATE `user_profiles` SET `seller_rejection_reason` = ?, `updated_at` = ? WHERE `user_id` = ?")
            ->execute([$reason, $now, $targetId]);
        NotificationService::send($targetId, 'seller_' . $dec, 'Seller status update', "Your seller status was set to {$dec}." . ($reason ? " Note: {$reason}" : ''), 'seller/become-seller.php', 'alert-circle');
        log_admin_action($aid, 'seller_' . $dec, 'user', $targetId, "Set seller @{$target['username']} to {$dec}.");
        flash('info', "Seller @{$target['username']} marked as {$dec}.");
    } elseif ($dec === 'toggle_verified') {
        $pdo->prepare("UPDATE `users` SET `is_verified_seller` = CASE WHEN `is_verified_seller` = 1 THEN 0 ELSE 1 END, `updated_at` = ? WHERE `id` = ?")
            ->execute([$now, $targetId]);
        log_admin_action($aid, 'toggle_verified_seller', 'user', $targetId, "Toggled verified seller badge for @{$target['username']}.");
        flash('success', "Verified badge toggled for @{$target['username']}.");
    }
    redirect(url('admin/sellers.php'));
}

if ($action === 'custom_commission') {
    $raw = trim((string)($_POST['custom_commission_rate'] ?? ''));
    if ($raw === '') {
        $pdo->prepare("UPDATE `user_profiles` SET `custom_commission_rate` = NULL, `updated_at` = ? WHERE `user_id` = ?")
            ->execute([$now, $targetId]);
        log_admin_action($aid, 'clear_custom_commission', 'user', $targetId, "Reset @{$target['username']} to global commission.");
        flash('info', "Seller @{$target['username']} reset to global default commission rate.");
    } else {
        $rate = (float)$raw;
        if ($rate < 0 || $rate > 50) {
            flash('error', 'Custom commission must be between 0% and 50% (or leave blank for default).');
            redirect(url('admin/sellers.php'));
        }
        $fmt = number_format($rate, 2, '.', '');
        $pdo->prepare("UPDATE `user_profiles` SET `custom_commission_rate` = ?, `updated_at` = ? WHERE `user_id` = ?")
            ->execute([$fmt, $now, $targetId]);
        log_admin_action($aid, 'set_custom_commission', 'user', $targetId, "Set @{$target['username']} commission rate to {$fmt}%.");
        flash('success', "Custom commission for @{$target['username']} set to {$fmt}%.");
    }
    redirect(url('admin/sellers.php'));
}

redirect($back);
