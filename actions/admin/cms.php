<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/pages.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$now = date('Y-m-d H:i:s');

if ($action === 'page_save') {
    $id = (int)($_POST['id'] ?? 0);
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 160);
    $slug = slugify(trim((string)($_POST['slug'] ?? '')) ?: $title);
    $subtitle = mb_substr(trim((string)($_POST['subtitle'] ?? '')), 0, 260) ?: null;
    $content = trim((string)($_POST['content'] ?? ''));
    $metaTitle = mb_substr(trim((string)($_POST['meta_title'] ?? '')), 0, 160) ?: null;
    $metaDesc = mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 300) ?: null;
    $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? (string)$_POST['status'] : 'published';

    if ($title === '' || $slug === '' || $content === '') {
        flash('error', 'Page title, slug, and content are required.');
        redirect(url('admin/pages.php?tab=pages'));
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                "UPDATE `pages`
                 SET `slug` = ?, `title` = ?, `subtitle` = ?, `content` = ?, `meta_title` = ?,
                     `meta_description` = ?, `status` = ?, `updated_by` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([$slug, $title, $subtitle, $content, $metaTitle, $metaDesc, $status, $aid, $now, $id]);
            log_admin_action($aid, 'update_page', 'page', $id, "Updated CMS page /{$slug}.");
        } else {
            $pdo->prepare(
                "INSERT INTO `pages` (`slug`, `title`, `subtitle`, `content`, `meta_title`, `meta_description`, `status`, `updated_by`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$slug, $title, $subtitle, $content, $metaTitle, $metaDesc, $status, $aid, $now, $now]);
            log_admin_action($aid, 'create_page', 'page', (int)$pdo->lastInsertId(), "Created CMS page /{$slug}.");
        }
        flash('success', "CMS page \"{$title}\" saved.");
    } catch (Throwable $e) {
        flash('error', 'A page with that slug already exists.');
    }
    redirect(url('admin/pages.php?tab=pages'));
}

if ($action === 'page_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `pages` WHERE `id` = ?")->execute([$id]);
    log_admin_action($aid, 'delete_page', 'page', $id, 'Deleted CMS page.');
    flash('info', 'CMS page deleted.');
    redirect(url('admin/pages.php?tab=pages'));
}

if ($action === 'faq_save') {
    $id = (int)($_POST['id'] ?? 0);
    $cat = mb_substr(trim((string)($_POST['category'] ?? 'General')), 0, 60) ?: 'General';
    $question = mb_substr(trim((string)($_POST['question'] ?? '')), 0, 260);
    $answer = mb_substr(trim((string)($_POST['answer'] ?? '')), 0, 2000);
    $sort = (int)($_POST['sort_order'] ?? 1);
    $home = !empty($_POST['show_on_homepage']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string)$_POST['status'] : 'active';

    if ($question === '' || $answer === '') {
        flash('error', 'FAQ question and answer are required.');
        redirect(url('admin/pages.php?tab=faqs'));
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                "UPDATE `faqs` SET `category` = ?, `question` = ?, `answer` = ?, `show_on_homepage` = ?, `sort_order` = ?, `status` = ?, `updated_at` = ? WHERE `id` = ?"
            )->execute([$cat, $question, $answer, $home, $sort, $status, $now, $id]);
            log_admin_action($aid, 'update_faq', 'faq', $id, 'Updated FAQ item.');
        } else {
            $pdo->prepare(
                "INSERT INTO `faqs` (`category`, `question`, `answer`, `show_on_homepage`, `sort_order`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$cat, $question, $answer, $home, $sort, $status, $now, $now]);
            log_admin_action($aid, 'create_faq', 'faq', (int)$pdo->lastInsertId(), 'Created FAQ item.');
        }
        flash('success', 'FAQ saved successfully.');
    } catch (Throwable $e) {
        flash('error', 'An identical FAQ question already exists.');
    }
    redirect(url('admin/pages.php?tab=faqs'));
}

if ($action === 'faq_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `faqs` WHERE `id` = ?")->execute([$id]);
    log_admin_action($aid, 'delete_faq', 'faq', $id, 'Deleted FAQ item.');
    flash('info', 'FAQ deleted.');
    redirect(url('admin/pages.php?tab=faqs'));
}

if ($action === 'newsletter_export') {
    $rows = $pdo->query("SELECT `email`, `status`, `subscribed_at` FROM `newsletter_subscribers` ORDER BY `id` DESC")->fetchAll();
    log_admin_action($aid, 'export_newsletter', 'newsletter', null, 'Exported ' . count($rows) . ' subscribers CSV.');
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'status', 'subscribed_at']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['email'], $r['status'], $r['subscribed_at']]);
    }
    fclose($out);
    exit;
}

if ($action === 'newsletter_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `newsletter_subscribers` WHERE `id` = ?")->execute([$id]);
    flash('info', 'Subscriber removed.');
    redirect(url('admin/pages.php?tab=newsletter'));
}

if ($action === 'db_backup') {
    $dbPath = Database::databasePath();
    $tmpBackup = sys_get_temp_dir() . '/codevault-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.sqlite';
    $escaped = str_replace("'", "''", $tmpBackup);
    try {
        $pdo->exec("VACUUM INTO '{$escaped}'");
        if (!is_file($tmpBackup)) {
            copy($dbPath, $tmpBackup);
        }
        $size = (int)filesize($tmpBackup);
        $fname = 'codevault-backup-' . date('Ymd-His') . '.sqlite';
        $pdo->prepare("INSERT INTO `backups` (`filename`, `private_path`, `file_size_bytes`, `created_by`, `created_at`) VALUES (?, ?, ?, ?, ?)")
            ->execute([$fname, 'direct-download', $size, $aid, $now]);
        log_admin_action($aid, 'download_db_backup', 'backup', null, "Exported clean SQLite snapshot ({$fname}).");

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/x-sqlite3');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . $size);
        header('X-Content-Type-Options: nosniff');
        readfile($tmpBackup);
        @unlink($tmpBackup);
        exit;
    } catch (Throwable $e) {
        @unlink($tmpBackup);
        flash('error', 'Could not generate database backup: ' . $e->getMessage());
        redirect(url('admin/logs.php?tab=backup'));
    }
}

if ($action === 'system_cleanup') {
    $pdo->exec("DELETE FROM `login_attempts` WHERE `attempted_at` < datetime('now', '-7 days', 'localtime')");
    $pdo->exec("UPDATE `password_resets` SET `status` = 'expired' WHERE `status` = 'pending' AND `expires_at` < datetime('now', 'localtime')");
    $pdo->exec("DELETE FROM `user_sessions` WHERE `status` <> 'active' AND `last_activity_at` < datetime('now', '-14 days', 'localtime')");
    $pdo->exec("DELETE FROM `carts` WHERE `user_id` IS NULL AND `updated_at` < datetime('now', '-14 days', 'localtime')");
    $pdo->exec("PRAGMA wal_checkpoint(TRUNCATE)");
    log_admin_action($aid, 'system_cleanup', 'system', null, 'Purged stale sessions, login throttles, expired resets, and checkpointed WAL.');
    flash('success', 'System maintenance cleanup and SQLite WAL checkpoint completed.');
    redirect(url('admin/logs.php?tab=backup'));
}

redirect(url('admin/pages.php'));
