<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/projects.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$pid = (int)($_POST['project_id'] ?? 0);
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$back = ($returnTo !== '' && str_starts_with($returnTo, 'admin/')) ? url($returnTo) : url('admin/projects.php');
$now = date('Y-m-d H:i:s');

if ($pid <= 0) {
    flash('error', 'Invalid project.');
    redirect($back);
}

if ($action === 'approve') {
    $res = ProjectService::approveProject($pid, $aid);
    flash($res['success'] ? 'success' : 'error', (string)$res['message']);
    redirect($back);
}

if ($action === 'reject') {
    $reason = trim((string)($_POST['rejection_reason'] ?? ''));
    $res = ProjectService::rejectProject($pid, $aid, $reason);
    flash($res['success'] ? 'info' : 'error', (string)$res['message']);
    redirect($back);
}

if ($action === 'toggle_featured') {
    $pdo->prepare("UPDATE `projects` SET `is_featured` = CASE WHEN `is_featured` = 1 THEN 0 ELSE 1 END, `updated_at` = ? WHERE `id` = ?")
        ->execute([$now, $pid]);
    log_admin_action($aid, 'toggle_featured_project', 'project', $pid, 'Toggled featured flag.');
    flash('success', 'Featured flag updated.');
    redirect($back);
}

if ($action === 'toggle_trending') {
    $pdo->prepare("UPDATE `projects` SET `is_trending` = CASE WHEN `is_trending` = 1 THEN 0 ELSE 1 END, `updated_at` = ? WHERE `id` = ?")
        ->execute([$now, $pid]);
    log_admin_action($aid, 'toggle_trending_project', 'project', $pid, 'Toggled trending flag.');
    flash('success', 'Trending flag updated.');
    redirect($back);
}

if ($action === 'toggle_visibility') {
    $pdo->prepare("UPDATE `projects` SET `status` = CASE WHEN `status` = 'hidden' THEN 'approved' ELSE 'hidden' END, `updated_at` = ? WHERE `id` = ?")
        ->execute([$now, $pid]);
    log_admin_action($aid, 'toggle_project_visibility', 'project', $pid, 'Toggled listing visibility.');
    flash('info', 'Project visibility updated.');
    redirect($back);
}

if ($action === 'delete') {
    $salesCheck = $pdo->prepare("SELECT COUNT(*) FROM `order_items` WHERE `project_id` = ?");
    $salesCheck->execute([$pid]);
    if ((int)$salesCheck->fetchColumn() > 0) {
        $pdo->prepare("UPDATE `projects` SET `status` = 'archived', `is_featured` = 0, `is_trending` = 0, `updated_at` = ? WHERE `id` = ?")->execute([$now, $pid]);
        log_admin_action($aid, 'archive_project', 'project', $pid, 'Archived project with buyer orders.');
        flash('info', 'Project has active buyer licenses, so it was safely archived from listings.');
    } else {
        $pdo->prepare("DELETE FROM `projects` WHERE `id` = ?")->execute([$pid]);
        log_admin_action($aid, 'delete_project', 'project', $pid, 'Permanently deleted project.');
        flash('info', 'Project permanently deleted.');
    }
    redirect(url('admin/projects.php'));
}

redirect($back);
