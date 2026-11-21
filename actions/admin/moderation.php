<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/dashboard.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$now = date('Y-m-d H:i:s');

// Reviews moderation
if ($action === 'review_status') {
    $rid = (int)($_POST['review_id'] ?? 0);
    $st = (string)($_POST['status'] ?? 'hidden');
    $rStmt = $pdo->prepare(
        "SELECT r.project_id, p.seller_id FROM `reviews` r
         INNER JOIN `projects` p ON p.id = r.project_id
         WHERE r.id = ? LIMIT 1"
    );
    $rStmt->execute([$rid]);
    $rev = $rStmt->fetch();
    if ($rev) {
        if ($st === 'delete') {
            $pdo->prepare("DELETE FROM `reviews` WHERE `id` = ?")->execute([$rid]);
        } elseif ($st === 'toggle_testimonial') {
            $pdo->prepare("UPDATE `reviews` SET `is_featured_testimonial` = CASE WHEN `is_featured_testimonial` = 1 THEN 0 ELSE 1 END, `updated_at` = ? WHERE `id` = ?")
                ->execute([$now, $rid]);
        } elseif (in_array($st, ['published', 'hidden', 'flagged'], true)) {
            $pdo->prepare("UPDATE `reviews` SET `status` = ?, `updated_at` = ? WHERE `id` = ?")->execute([$st, $now, $rid]);
        }
        $pid = (int)$rev['project_id'];
        $sid = (int)$rev['seller_id'];
        $pdo->prepare(
            "UPDATE `projects` SET
                `reviews_count` = (SELECT COUNT(*) FROM `reviews` WHERE `project_id` = ? AND `status` = 'published'),
                `average_rating` = COALESCE((SELECT ROUND(AVG(`rating`), 2) FROM `reviews` WHERE `project_id` = ? AND `status` = 'published'), 0)
             WHERE `id` = ?"
        )->execute([$pid, $pid, $pid]);
        $pdo->prepare(
            "UPDATE `user_profiles` SET
                `total_reviews_count` = (SELECT COUNT(*) FROM `reviews` r2 INNER JOIN `projects` p2 ON p2.id = r2.project_id WHERE p2.seller_id = ? AND r2.status = 'published'),
                `average_rating` = COALESCE((SELECT ROUND(AVG(r2.rating), 2) FROM `reviews` r2 INNER JOIN `projects` p2 ON p2.id = r2.project_id WHERE p2.seller_id = ? AND r2.status = 'published'), 0)
             WHERE `user_id` = ?"
        )->execute([$sid, $sid, $sid]);
        log_admin_action($aid, 'moderate_review', 'review', $rid, "Review action -> {$st}.");
        flash('info', 'Review updated and ratings recalculated.');
    }
    redirect(url('admin/reviews.php'));
}

// Review report handling
if ($action === 'resolve_review_report') {
    $repId = (int)($_POST['report_id'] ?? 0);
    $st = in_array($_POST['status'] ?? '', ['reviewed', 'dismissed'], true) ? (string)$_POST['status'] : 'reviewed';
    $hideReview = !empty($_POST['hide_review']);

    $rStmt = $pdo->prepare("SELECT `review_id` FROM `review_reports` WHERE `id` = ? LIMIT 1");
    $rStmt->execute([$repId]);
    $rep = $rStmt->fetch();
    if ($rep) {
        $pdo->prepare("UPDATE `review_reports` SET `status` = ? WHERE `id` = ?")->execute([$st, $repId]);
        if ($hideReview) {
            $pdo->prepare("UPDATE `reviews` SET `status` = 'hidden', `updated_at` = ? WHERE `id` = ?")->execute([$now, (int)$rep['review_id']]);
        }
        log_admin_action($aid, 'resolve_review_report', 'report', $repId, "Marked report {$st}.");
        flash('success', "Report marked as {$st}.");
    }
    redirect(url('admin/reviews.php?tab=reports'));
}

// Product Q&A moderation
if ($action === 'question_status') {
    $qid = (int)($_POST['question_id'] ?? 0);
    $st = (string)($_POST['status'] ?? 'hidden');
    if ($st === 'delete') {
        $pdo->prepare("DELETE FROM `project_questions` WHERE `id` = ?")->execute([$qid]);
    } elseif (in_array($st, ['visible', 'hidden'], true)) {
        $pdo->prepare("UPDATE `project_questions` SET `status` = ?, `updated_at` = ? WHERE `id` = ?")->execute([$st, $now, $qid]);
    }
    log_admin_action($aid, 'moderate_question', 'question', $qid, "Question -> {$st}.");
    flash('info', 'Question moderated.');
    redirect(url('admin/reviews.php?tab=questions'));
}

// Support ticket reply & status
if ($action === 'ticket_reply') {
    $tid = (int)($_POST['ticket_id'] ?? 0);
    $msg = trim((string)($_POST['message'] ?? ''));
    $nextStatus = (string)($_POST['status'] ?? 'answered');
    if (!in_array($nextStatus, ['open', 'in_progress', 'answered', 'resolved', 'closed'], true)) {
        $nextStatus = 'answered';
    }

    $tStmt = $pdo->prepare("SELECT * FROM `support_tickets` WHERE `id` = ? LIMIT 1");
    $tStmt->execute([$tid]);
    $ticket = $tStmt->fetch();
    if (!$ticket) {
        flash('error', 'Ticket not found.');
        redirect(url('admin/support.php'));
    }

    if ($msg !== '') {
        $pdo->prepare(
            "INSERT INTO `ticket_messages` (`ticket_id`, `sender_id`, `sender_role`, `message`, `created_at`)
             VALUES (?, ?, 'admin', ?, ?)"
        )->execute([$tid, $aid, mb_substr($msg, 0, 4000), $now]);

        if (!empty($ticket['user_id'])) {
            NotificationService::send(
                (int)$ticket['user_id'],
                'ticket_reply',
                "Support reply on {$ticket['ticket_number']}",
                mb_substr($msg, 0, 140),
                'buyer/support.php?ticket=' . urlencode((string)$ticket['ticket_number']),
                'life-buoy'
            );
        }
    }

    $pdo->prepare("UPDATE `support_tickets` SET `status` = ?, `last_reply_at` = ?, `updated_at` = ? WHERE `id` = ?")
        ->execute([$nextStatus, $now, $now, $tid]);

    log_admin_action($aid, 'support_reply', 'ticket', $tid, "Updated ticket {$ticket['ticket_number']} -> {$nextStatus}.");
    flash('success', "Ticket {$ticket['ticket_number']} updated.");
    redirect(url('admin/support.php?ticket=' . urlencode((string)$ticket['ticket_number'])));
}

// Broadcast / targeted notifications
if ($action === 'send_notification') {
    $audience = (string)($_POST['audience'] ?? 'all');
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 140);
    $body = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 500);
    $link = mb_substr(trim((string)($_POST['action_url'] ?? '')), 0, 200) ?: null;
    $specificId = (int)($_POST['target_user_id'] ?? 0);

    if ($title === '' || $body === '') {
        flash('error', 'Notification title and message are required.');
        redirect(url('admin/notifications.php'));
    }

    if ($audience === 'user' && $specificId > 0) {
        $userIds = [$specificId];
    } elseif ($audience === 'sellers') {
        $userIds = $pdo->query("SELECT `id` FROM `users` WHERE `is_seller` = 1 AND `status` = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($audience === 'buyers') {
        $userIds = $pdo->query("SELECT `id` FROM `users` WHERE `role` = 'buyer' AND `status` = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $userIds = $pdo->query("SELECT `id` FROM `users` WHERE `status` = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    }

    $sent = 0;
    foreach ($userIds as $uid) {
        NotificationService::send((int)$uid, 'announcement', $title, $body, $link, 'bell');
        $sent++;
    }

    log_admin_action($aid, 'broadcast_notification', 'notification', null, "Sent '{$title}' to {$sent} user(s) ({$audience}).");
    flash('success', "Announcement delivered to {$sent} active user(s).");
    redirect(url('admin/notifications.php'));
}

redirect(url('admin/dashboard.php'));
