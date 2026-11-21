<?php
declare(strict_types=1);

/**
 * Private Payment Proof & Support Ticket Attachment Streamer
 * Strictly authorizes owning user or marketplace administrator with path confinement.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_admin() ?: require_login();
$proofId = (int)($_GET['id'] ?? 0);
$ticketMsgId = (int)($_GET['msg'] ?? 0);

if ($proofId > 0) {
    $stmt = db()->prepare(
        "SELECT pp.*, p.buyer_id
         FROM `payment_proofs` pp
         INNER JOIN `payments` p ON p.id = pp.payment_id
         WHERE pp.id = ? LIMIT 1"
    );
    $stmt->execute([$proofId]);
    $row = $stmt->fetch();
    if (!$row || (!is_admin($user) && (int)$row['buyer_id'] !== (int)$user['id'])) {
        http_response_code(403);
        exit('Forbidden');
    }
    $root = realpath(PAYMENT_PROOFS_PATH);
    $full = realpath(ROOT_PATH . '/' . ltrim((string)$row['private_path'], '/'));
    $mime = (string)$row['mime_type'];
    $name = (string)$row['original_name'];
} elseif ($ticketMsgId > 0) {
    $stmt = db()->prepare(
        "SELECT tm.attachment_path, tm.attachment_name, st.user_id
         FROM `ticket_messages` tm
         INNER JOIN `support_tickets` st ON st.id = tm.ticket_id
         WHERE tm.id = ? AND tm.attachment_path IS NOT NULL LIMIT 1"
    );
    $stmt->execute([$ticketMsgId]);
    $row = $stmt->fetch();
    if (!$row || (!is_admin($user) && (int)$row['user_id'] !== (int)$user['id'])) {
        http_response_code(403);
        exit('Forbidden');
    }
    $root = realpath(PRIVATE_PATH . '/ticket-attachments');
    $full = realpath(ROOT_PATH . '/' . ltrim((string)$row['attachment_path'], '/'));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $full && is_file($full) ? (string)$finfo->file($full) : 'application/octet-stream';
    $name = (string)($row['attachment_name'] ?: 'attachment.png');
} else {
    http_response_code(400);
    exit('Bad request');
}

if (!$root || !$full || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) {
    http_response_code(404);
    exit('Not found');
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(403);
    exit('Unsupported media');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) . '"');
header('Content-Length: ' . (int)filesize($full));
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($full);
exit;
