<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/become-seller.php'));
}

$user = require_login();
csrf_require();

if (site_setting('seller_registration_enabled', '1') !== '1') {
    flash('warning', 'Seller applications are temporarily paused.');
    redirect(url('seller/become-seller.php'));
}

if (in_array($user['seller_status'], ['approved', 'pending', 'suspended'], true)) {
    flash('info', 'Your seller profile status is currently: ' . ucfirst((string)$user['seller_status']) . '.');
    redirect(url('seller/become-seller.php'));
}

$v = (new Validator($_POST))
    ->required('store_name', 'Store / Studio name')->minLength('store_name', 3, 'Store name')->maxLength('store_name', 120, 'Store name')
    ->required('headline', 'Specialty headline')->minLength('headline', 8, 'Specialty headline')->maxLength('headline', 180, 'Specialty headline')
    ->required('skills', 'Primary technologies')->maxLength('skills', 400, 'Primary technologies')
    ->required('payout_method', 'Preferred withdrawal channel')->inList('payout_method', ['bkash', 'nagad', 'rocket'], 'Payout channel')
    ->required('payout_number', 'Account number')->phone('payout_number', 'Payout account number')
    ->required('application_note', 'Portfolio & project overview')->minLength('application_note', 20, 'Portfolio overview')
    ->url('portfolio_url', 'Portfolio / GitHub URL');

if ($v->fails()) {
    set_old_input($_POST);
    flash('error', $v->firstError());
    redirect(url('seller/become-seller.php'));
}

$uid = (int)$user['id'];
$autoApprove = site_setting('seller_auto_approve', '0') === '1';
$newStatus = $autoApprove ? 'approved' : 'pending';
$storeName = trim((string)$_POST['store_name']);
$storeSlug = slugify($storeName) . '-' . $uid;

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare(
        "UPDATE `users` SET `is_seller` = ?, `seller_status` = ?, `role` = ? WHERE `id` = ?"
    )->execute([
        $autoApprove ? 1 : 0,
        $newStatus,
        ($user['role'] === 'admin') ? 'admin' : ($autoApprove ? 'seller' : 'buyer'),
        $uid,
    ]);

    $nowTs = date('Y-m-d H:i:s');
    $pdo->prepare(
        "UPDATE `user_profiles`
         SET `store_name` = ?, `store_slug` = ?, `headline` = ?, `skills` = ?,
             `portfolio_url` = ?, `payout_method` = ?, `payout_number` = ?,
             `seller_application_note` = ?, `seller_applied_at` = ?,
             `seller_approved_at` = ?, `updated_at` = ?
         WHERE `user_id` = ?"
    )->execute([
        $storeName,
        $storeSlug,
        trim((string)$_POST['headline']),
        trim((string)$_POST['skills']),
        trim((string)($_POST['portfolio_url'] ?? '')) ?: null,
        $_POST['payout_method'],
        trim((string)$_POST['payout_number']),
        trim((string)$_POST['application_note']),
        $nowTs,
        $autoApprove ? $nowTs : null,
        $nowTs,
        $uid,
    ]);

    if ($autoApprove) {
        $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$uid]);
    }

    NotificationService::send(
        $uid,
        'seller_application',
        $autoApprove ? 'Seller Studio approved' : 'Seller application submitted',
        $autoApprove
            ? "Congratulations! Your studio \"{$storeName}\" is active. Start uploading your digital projects."
            : "We received your application for \"{$storeName}\". Our administrators will review it shortly.",
        $autoApprove ? 'seller/dashboard.php' : 'seller/become-seller.php',
        'store'
    );

    NotificationService::notifyAdmins(
        'seller_application',
        'New seller studio: ' . $storeName,
        "{$user['full_name']} (@{$user['username']}) applied for seller privileges.",
        'admin/sellers.php',
        'store'
    );

    $pdo->commit();
    flash('success', $autoApprove ? 'Your Seller Studio is active! Start publishing your projects.' : 'Application submitted! Our review team will notify you upon approval.');
    redirect(url($autoApprove ? 'seller/dashboard.php' : 'seller/become-seller.php'));
} catch (Throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Could not submit application. Please try again.');
}

redirect(url('seller/become-seller.php'));
