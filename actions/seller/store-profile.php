<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/store-profile.php'));
}

$user = require_seller();
csrf_require();

$v = (new Validator($_POST))
    ->required('store_name', 'Store name')->minLength('store_name', 3, 'Store name')->maxLength('store_name', 120, 'Store name')
    ->required('headline', 'Headline')->minLength('headline', 5, 'Headline')->maxLength('headline', 180, 'Headline')
    ->maxLength('bio', 1200, 'Store bio')
    ->maxLength('skills', 400, 'Technologies')
    ->email('support_email', 'Support email')
    ->url('website_url', 'Website URL')
    ->url('github_url', 'GitHub URL')
    ->url('portfolio_url', 'Portfolio URL')
    ->required('payout_method', 'Payout wallet')->inList('payout_method', ['bkash', 'nagad', 'rocket'], 'Payout wallet')
    ->required('payout_number', 'Payout mobile number')->phone('payout_number', 'Payout mobile number')
    ->inList('payout_account_type', ['personal', 'agent', 'merchant'], 'Account type');

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect(url('seller/store-profile.php'));
}

$uid = (int)$user['id'];
$pdo = db();
$now = date('Y-m-d H:i:s');

if (!empty($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $av = UploadService::storeAvatar($_FILES['avatar'], $uid);
    if (!$av['success']) {
        flash('error', (string)$av['message']);
        redirect(url('seller/store-profile.php'));
    }
    $pdo->prepare("UPDATE `users` SET `avatar` = ?, `updated_at` = ? WHERE `id` = ?")->execute([$av['path'], $now, $uid]);
}

$storeName = trim((string)$_POST['store_name']);
$baseSlug = slugify($storeName) ?: (string)$user['username'];
$dup = $pdo->prepare("SELECT COUNT(*) FROM `user_profiles` WHERE `store_slug` = ? AND `user_id` <> ?");
$dup->execute([$baseSlug, $uid]);
$storeSlug = (int)$dup->fetchColumn() > 0 ? ($baseSlug . '-' . $uid) : $baseSlug;

$pdo->prepare(
    "UPDATE `user_profiles`
     SET `store_name` = ?, `store_slug` = ?, `headline` = ?, `bio` = ?, `skills` = ?,
         `support_email` = ?, `response_time` = ?, `website_url` = ?, `github_url` = ?,
         `portfolio_url` = ?, `payout_method` = ?, `payout_number` = ?, `payout_account_type` = ?,
         `updated_at` = ?
     WHERE `user_id` = ?"
)->execute([
    $storeName,
    $storeSlug,
    trim((string)$_POST['headline']),
    trim((string)($_POST['bio'] ?? '')) ?: null,
    trim((string)($_POST['skills'] ?? '')) ?: null,
    trim((string)($_POST['support_email'] ?? '')) ?: null,
    mb_substr(trim((string)($_POST['response_time'] ?? 'Usually replies within 2 hours')), 0, 80),
    trim((string)($_POST['website_url'] ?? '')) ?: null,
    trim((string)($_POST['github_url'] ?? '')) ?: null,
    trim((string)($_POST['portfolio_url'] ?? '')) ?: null,
    (string)$_POST['payout_method'],
    trim((string)$_POST['payout_number']),
    (string)($_POST['payout_account_type'] ?? 'personal'),
    $now,
    $uid,
]);

flash('success', 'Storefront profile and payout settings saved.');
redirect(url('seller/store-profile.php'));
