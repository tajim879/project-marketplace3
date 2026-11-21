<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('buyer/support.php'));
}

$user = require_login();
csrf_require();

$uid = (int)$user['id'];
$action = (string)($_POST['action'] ?? 'create');
$pdo = db();

if ($action === 'create') {
    $allowedCats = ['order', 'payment', 'download', 'technical', 'seller', 'refund', 'general'];
    $v = (new Validator($_POST))
        ->required('subject', 'Subject')->minLength('subject', 5, 'Subject')->maxLength('subject', 190, 'Subject')
        ->required('category', 'Category')->inList('category', $allowedCats, 'Category')
        ->required('message', 'Description')->minLength('message', 12, 'Description')->maxLength('message', 4000, 'Description');

    if ($v->fails()) {
        flash('error', $v->firstError());
        redirect(url('buyer/support.php'));
    }

    $attachment = UploadService::storeSupportAttachment($_FILES['attachment'] ?? [], $uid);
    if (!$attachment['success']) {
        flash('error', $attachment['message']);
        redirect(url('buyer/support.php'));
    }

    $ticketNo = generate_unique_code('TKT');
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO `support_tickets` (`ticket_number`, `user_id`, `category`, `priority`, `subject`, `status`, `last_reply_at`)
             VALUES (?, ?, ?, 'normal', ?, 'open', ?)"
        )->execute([$ticketNo, $uid, $_POST['category'], trim((string)$_POST['subject']), date('Y-m-d H:i:s')]);
        $ticketId = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO `ticket_messages` (`ticket_id`, `sender_id`, `sender_role`, `message`, `attachment_path`, `attachment_name`)
             VALUES (?, ?, 'user', ?, ?, ?)"
        )->execute([$ticketId, $uid, trim((string)$_POST['message']), $attachment['path'], $attachment['name']]);

        NotificationService::notifyAdmins(
            'support_ticket',
            "New ticket {$ticketNo}",
            "{$user['full_name']} opened a {$_POST['category']} support ticket.",
            'admin/support/view.php?id=' . $ticketId,
            'life-buoy'
        );

        $pdo->commit();
        flash('success', "Support ticket {$ticketNo} created. Our team will respond shortly.");
        redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Failed to create ticket. Please try again.');
        redirect(url('buyer/support.php'));
    }
}

if ($action === 'reply') {
    $ticketNo = trim((string)($_POST['ticket_number'] ?? ''));
    $msg = trim((string)($_POST['message'] ?? ''));

    $stmt = $pdo->prepare("SELECT * FROM `support_tickets` WHERE `ticket_number` = ? AND `user_id` = ? LIMIT 1");
    $stmt->execute([$ticketNo, $uid]);
    $ticket = $stmt->fetch();

    if (!$ticket || $ticket['status'] === 'closed') {
        flash('error', 'This ticket is closed or inaccessible.');
        redirect(url('buyer/support.php'));
    }
    if (mb_strlen($msg, 'UTF-8') < 3) {
        flash('error', 'Please enter a reply message.');
        redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
    }

    $attachment = UploadService::storeSupportAttachment($_FILES['attachment'] ?? [], $uid);
    if (!$attachment['success']) {
        flash('error', $attachment['message']);
        redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
    }

    $pdo->prepare(
        "INSERT INTO `ticket_messages` (`ticket_id`, `sender_id`, `sender_role`, `message`, `attachment_path`, `attachment_name`)
         VALUES (?, ?, 'user', ?, ?, ?)"
    )->execute([$ticket['id'], $uid, $msg, $attachment['path'], $attachment['name']]);

    $pdo->prepare("UPDATE `support_tickets` SET `status` = 'open', `last_reply_at` = ?, `updated_at` = ? WHERE `id` = ?")
        ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $ticket['id']]);

    flash('success', 'Your reply has been added.');
    redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
}

if ($action === 'close') {
    $ticketNo = trim((string)($_POST['ticket_number'] ?? ''));
    $pdo->prepare("UPDATE `support_tickets` SET `status` = 'closed', `updated_at` = ? WHERE `ticket_number` = ? AND `user_id` = ?")
        ->execute([date('Y-m-d H:i:s'), $ticketNo, $uid]);
    flash('info', "Ticket {$ticketNo} has been closed.");
    redirect(url('buyer/support.php?ticket=' . urlencode($ticketNo)));
}

redirect(url('buyer/support.php'));
