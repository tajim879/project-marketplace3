<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('buyer/reviews.php'));
}

$user = require_login();
csrf_require();

$orderItemId = (int)($_POST['order_item_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$comment = trim((string)($_POST['comment'] ?? ''));

if ($rating < 1 || $rating > 5) {
    flash('error', 'Please select a star rating between 1 and 5.');
    redirect(url('buyer/reviews.php'));
}

$v = (new Validator(['comment' => $comment, 'title' => $title]))
    ->required('comment', 'Written review')
    ->minLength('comment', 12, 'Written review')
    ->maxLength('comment', 2000, 'Written review')
    ->maxLength('title', 150, 'Review headline');

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect(url('buyer/reviews.php'));
}

$pdo = db();
// Verify authenticated user owns this paid order item
$stmt = $pdo->prepare(
    "SELECT oi.*, o.buyer_id, o.status AS order_status, p.slug AS project_slug, p.title AS project_title
     FROM `order_items` oi
     INNER JOIN `orders` o ON o.id = oi.order_id
     INNER JOIN `projects` p ON p.id = oi.project_id
     WHERE oi.id = ? AND o.buyer_id = ? AND o.status = 'paid' AND oi.status = 'active'
     LIMIT 1"
);
$stmt->execute([$orderItemId, $user['id']]);
$item = $stmt->fetch();

if (!$item) {
    flash('error', 'Only verified buyers of paid orders may review this project.');
    redirect(url('buyer/reviews.php'));
}

$existingStmt = $pdo->prepare("SELECT `id` FROM `reviews` WHERE `order_item_id` = ? AND `buyer_id` = ? LIMIT 1");
$existingStmt->execute([$orderItemId, $user['id']]);
$existingId = $existingStmt->fetchColumn();

$pdo->beginTransaction();
try {
    if ($existingId) {
        $pdo->prepare(
            "UPDATE `reviews` SET `rating` = ?, `title` = ?, `comment` = ?, `updated_at` = ?
             WHERE `id` = ? AND `buyer_id` = ?"
        )->execute([$rating, $title ?: null, $comment, date('Y-m-d H:i:s'), $existingId, $user['id']]);
        $msg = 'Your verified review has been updated.';
    } else {
        $pdo->prepare(
            "INSERT INTO `reviews` (`project_id`, `buyer_id`, `order_item_id`, `rating`, `title`, `comment`, `status`)
             VALUES (?, ?, ?, ?, ?, ?, 'published')"
        )->execute([$item['project_id'], $user['id'], $orderItemId, $rating, $title ?: null, $comment]);

        NotificationService::send(
            (int)$item['seller_id'],
            'new_review',
            "New {$rating}-star review on {$item['project_title']}",
            "{$user['full_name']} left a verified buyer review on your project.",
            url('project.php?slug=' . urlencode((string)$item['project_slug']) . '#reviews'),
            'star'
        );
        $msg = 'Thank you! Your verified review is now published.';
    }

    ProjectRepository::recalculateRating((int)$item['project_id']);
    $pdo->commit();
    flash('success', $msg);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Unable to save review. Please try again.');
}

$returnTo = trim((string)($_POST['return_to'] ?? ''));
if ($returnTo === 'project') {
    redirect(url('project.php?slug=' . urlencode((string)$item['project_slug']) . '#reviews'));
}
redirect(url('buyer/reviews.php'));
