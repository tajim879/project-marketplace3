<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = require_login();
csrf_require();

$uid = (int)$user['id'];
$action = (string)($_POST['action'] ?? '');

if ($action === 'mark_all_read') {
    NotificationService::markAllRead($uid);
    json_response(['success' => true, 'unread_count' => 0, 'message' => 'All notifications marked as read.']);
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    NotificationService::markRead($id, $uid);
    json_response(['success' => true, 'unread_count' => unread_notifications_count()]);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    NotificationService::delete($id, $uid);
    json_response(['success' => true, 'unread_count' => unread_notifications_count()]);
}

json_response(['success' => false, 'message' => 'Unknown notification action.'], 400);
