<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$orderItemId = (int)($_GET['item'] ?? 0);
$reviewProjectId = (int)($_GET['project'] ?? 0);

// Buyer downloads must always use the buyer's own session. If the same browser also has an
// admin session open, preferring the admin account made the ownership check fail (403).
// The admin session is only used for the admin/seller project-review download.
if ($orderItemId > 0) {
    $user = require_login();
} else {
    $user = current_admin() ?: require_login();
}

if ($orderItemId > 0) {
    $auth = DownloadService::authorizeAndResolve((int)$user['id'], $orderItemId);
    if (!$auth['allowed']) {
        flash('error', (string)$auth['message']);
        redirect(url('buyer/purchases.php'));
    }
    DownloadService::recordDownload((int)$user['id'], $auth['record']);
} elseif ($reviewProjectId > 0) {
    $auth = DownloadService::authorizeOwnerOrAdmin($user, $reviewProjectId);
    if (!$auth['allowed']) {
        flash('error', (string)$auth['message']);
        redirect(url('index.php'));
    }
} else {
    flash('error', 'Invalid download request.');
    redirect(url('buyer/downloads.php'));
}

$filePath = (string)$auth['file_path'];
$downloadName = (string)$auth['download_name'];
$fileSize = (int)filesize($filePath);

// Stream archive cleanly from non-public storage; never leak filesystem paths
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
