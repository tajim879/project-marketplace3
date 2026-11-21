<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('buyer/profile.php'));
}

$user = require_login();
csrf_require();

$v = (new Validator($_POST))
    ->required('full_name', 'Full name')->minLength('full_name', 2, 'Full name')->maxLength('full_name', 120, 'Full name')
    ->phone('phone', 'Phone number')
    ->maxLength('headline', 190, 'Professional headline')
    ->maxLength('bio', 1200, 'Bio')
    ->maxLength('city', 80, 'City')
    ->maxLength('country', 80, 'Country')
    ->maxLength('address', 240, 'Address')
    ->url('website_url', 'Website URL')
    ->url('github_url', 'GitHub URL');

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect(url('buyer/profile.php'));
}

$uid = (int)$user['id'];
$avatarPath = $user['avatar'];

if (!empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $up = UploadService::storeAvatar($_FILES['avatar'], $uid);
    if (!$up['success']) {
        flash('error', $up['message']);
        redirect(url('buyer/profile.php'));
    }
    $avatarPath = $up['path'];
}

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE `users` SET `full_name` = ?, `phone` = ?, `avatar` = ? WHERE `id` = ?")
        ->execute([
            trim((string)$_POST['full_name']),
            trim((string)($_POST['phone'] ?? '')) ?: null,
            $avatarPath,
            $uid,
        ]);

    $pdo->prepare(
        "INSERT INTO `user_profiles` (`user_id`, `headline`, `bio`, `city`, `country`, `address`, `website_url`, `github_url`, `updated_at`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT(`user_id`) DO UPDATE SET
            `headline` = excluded.`headline`,
            `bio` = excluded.`bio`,
            `city` = excluded.`city`,
            `country` = excluded.`country`,
            `address` = excluded.`address`,
            `website_url` = excluded.`website_url`,
            `github_url` = excluded.`github_url`,
            `updated_at` = excluded.`updated_at`"
    )->execute([
        $uid,
        trim((string)($_POST['headline'] ?? '')) ?: null,
        trim((string)($_POST['bio'] ?? '')) ?: null,
        trim((string)($_POST['city'] ?? '')) ?: null,
        trim((string)($_POST['country'] ?? 'Bangladesh')) ?: 'Bangladesh',
        trim((string)($_POST['address'] ?? '')) ?: null,
        trim((string)($_POST['website_url'] ?? '')) ?: null,
        trim((string)($_POST['github_url'] ?? '')) ?: null,
        date('Y-m-d H:i:s'),
    ]);

    log_security_event('profile_updated', 'Account profile information updated', 'info', $uid);
    $pdo->commit();
    flash('success', 'Your profile settings have been saved.');
} catch (Throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Could not update profile settings.');
}

redirect(url('buyer/profile.php'));
