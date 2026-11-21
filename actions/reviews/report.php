<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('projects.php'));
}

$user = require_login();
csrf_require();

$reviewId = (int)($_POST['review_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? ''));
$slug = trim((string)($_POST['project_slug'] ?? ''));

if ($reviewId <= 0 || mb_strlen($reason, 'UTF-8') < 5) {
    flash('error', 'Please provide a brief reason for reporting this review.');
    redirect(url('project.php?slug=' . urlencode($slug) . '#reviews'));
}

try {
    db()->prepare(
        "INSERT INTO `review_reports` (`review_id`, `reported_by`, `reason`, `status`)
         VALUES (?, ?, ?, 'pending')
         ON CONFLICT(`review_id`, `reported_by`) DO UPDATE SET `reason` = excluded.`reason`"
    )->execute([$reviewId, $user['id'], mb_substr($reason, 0, 250)]);

    NotificationService::notifyAdmins(
        'review_report',
        'Review flagged for moderation',
        "Review #{$reviewId} was reported by @{$user['username']}.",
        'admin/reviews/index.php',
        'flag'
    );
    flash('success', 'Report submitted to our moderation team.');
} catch (Throwable) {
    flash('error', 'Could not submit report.');
}

redirect(url('project.php?slug=' . urlencode($slug) . '#reviews'));
