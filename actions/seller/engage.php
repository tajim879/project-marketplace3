<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/dashboard.php'));
}

$user = require_seller();
csrf_require();

$action = (string)($_POST['action'] ?? '');
$uid = (int)$user['id'];
$pdo = db();
$now = date('Y-m-d H:i:s');

if ($action === 'answer_question') {
    $qId = (int)($_POST['question_id'] ?? 0);
    $answer = trim((string)($_POST['answer'] ?? ''));
    if ($qId <= 0 || mb_strlen($answer, 'UTF-8') < 3) {
        flash('error', 'Please enter a helpful answer.');
        redirect(url('seller/questions.php'));
    }

    $stmt = $pdo->prepare(
        "SELECT pq.*, p.seller_id, p.title AS project_title, p.slug AS project_slug
         FROM `project_questions` pq
         INNER JOIN `projects` p ON p.id = pq.project_id
         WHERE pq.id = ? LIMIT 1"
    );
    $stmt->execute([$qId]);
    $q = $stmt->fetch();

    if (!$q || (!is_admin($user) && (int)$q['seller_id'] !== $uid)) {
        flash('error', 'Question not found.');
        redirect(url('seller/questions.php'));
    }

        $pdo->prepare("UPDATE `project_questions` SET `answer_text` = ?, `answered_by` = ?, `answered_at` = ?, `updated_at` = ? WHERE `id` = ?")
        ->execute([mb_substr($answer, 0, 1200), $uid, $now, $now, $qId]);

    NotificationService::send(
        (int)$q['user_id'],
        'question_answered',
        "Author answered your question on {$q['project_title']}",
        mb_substr($answer, 0, 140),
        'project.php?slug=' . urlencode((string)$q['project_slug']) . '#questions',
        'help-circle'
    );

    flash('success', 'Answer published.');
    redirect(url('seller/questions.php'));
}

if ($action === 'reply_review') {
    $rId = (int)($_POST['review_id'] ?? 0);
    $reply = trim((string)($_POST['seller_reply'] ?? ''));
    if ($rId <= 0 || mb_strlen($reply, 'UTF-8') < 3) {
        flash('error', 'Please enter a reply.');
        redirect(url('seller/reviews.php'));
    }

    $stmt = $pdo->prepare(
        "SELECT r.*, p.seller_id, p.title AS project_title, p.slug AS project_slug
         FROM `reviews` r
         INNER JOIN `projects` p ON p.id = r.project_id
         WHERE r.id = ? LIMIT 1"
    );
    $stmt->execute([$rId]);
    $rev = $stmt->fetch();

    if (!$rev || (!is_admin($user) && (int)$rev['seller_id'] !== $uid)) {
        flash('error', 'Review not found.');
        redirect(url('seller/reviews.php'));
    }

    $pdo->prepare("UPDATE `reviews` SET `seller_reply` = ?, `seller_replied_at` = ?, `updated_at` = ? WHERE `id` = ?")
        ->execute([mb_substr($reply, 0, 800), $now, $now, $rId]);

    NotificationService::send(
        (int)$rev['buyer_id'],
        'review_reply',
        "Author replied to your review on {$rev['project_title']}",
        mb_substr($reply, 0, 140),
        'project.php?slug=' . urlencode((string)$rev['project_slug']) . '#reviews',
        'message-square'
    );

    flash('success', 'Author reply posted.');
    redirect(url('seller/reviews.php'));
}

if ($action === 'delete_screenshot') {
    $imgId = (int)($_POST['image_id'] ?? 0);
    $stmt = $pdo->prepare(
        "SELECT pi.*, p.seller_id FROM `project_images` pi
         INNER JOIN `projects` p ON p.id = pi.project_id WHERE pi.id = ? LIMIT 1"
    );
    $stmt->execute([$imgId]);
    $img = $stmt->fetch();
    if ($img && (is_admin($user) || (int)$img['seller_id'] === $uid)) {
        $pdo->prepare("DELETE FROM `project_images` WHERE `id` = ?")->execute([$imgId]);
        flash('info', 'Screenshot removed.');
        redirect(url('seller/edit-project.php?id=' . (int)$img['project_id']));
    }
}

if ($action === 'toggle_visibility') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    if ($p && can_manage_project($user, $p)) {
        if ($p['status'] === 'approved') {
            $pdo->prepare("UPDATE `projects` SET `status` = 'hidden', `updated_at` = ? WHERE `id` = ?")->execute([$now, $pid]);
            flash('info', 'Project paused and hidden from public listings.');
        } elseif ($p['status'] === 'hidden') {
            $pdo->prepare("UPDATE `projects` SET `status` = 'approved', `updated_at` = ? WHERE `id` = ?")->execute([$now, $pid]);
            flash('success', 'Project is live on the marketplace again.');
        }
    }
    redirect(url('seller/projects.php'));
}

redirect(url('seller/dashboard.php'));
