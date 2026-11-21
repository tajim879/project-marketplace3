<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/project-updates.php'));
}

$user = require_seller();
csrf_require();

$projectId = (int)($_POST['project_id'] ?? 0);
$version = preg_replace('/[^0-9a-zA-Z._-]/', '', trim((string)($_POST['version'] ?? ''))) ?: '';
$changelog = trim((string)($_POST['changelog'] ?? ''));

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
$stmt->execute([$projectId]);
$proj = $stmt->fetch();

if (!$proj || !can_manage_project($user, $proj)) {
    flash('error', 'Project not found or permission denied.');
    redirect(url('seller/project-updates.php'));
}

if ($version === '' || mb_strlen($changelog, 'UTF-8') < 8) {
    flash('error', 'Please enter a valid version number (e.g. 1.1.0) and release changelog.');
    redirect(url('seller/project-updates.php?project=' . $projectId));
}

$zipInfo = UploadService::storeProjectZip($_FILES['project_zip'] ?? [], (string)$proj['slug'], $version);
if (!$zipInfo['success']) {
    flash('error', (string)$zipInfo['message']);
    redirect(url('seller/project-updates.php?project=' . $projectId));
}

$now = date('Y-m-d H:i:s');
$pdo->beginTransaction();
try {
    // Mark previous files as non-current
    $pdo->prepare("UPDATE `project_files` SET `is_current` = 0, `status` = 'archived' WHERE `project_id` = ? AND `is_current` = 1")
        ->execute([$projectId]);

    $pdo->prepare(
        "INSERT INTO `project_files`
         (`project_id`, `version`, `original_name`, `stored_name`, `private_path`, `mime_type`,
          `file_size_bytes`, `checksum_sha256`, `is_inspected`, `is_current`, `status`, `created_at`)
         VALUES (?, ?, ?, ?, ?, 'application/zip', ?, ?, 1, 1, 'active', ?)"
    )->execute([
        $projectId,
        $version,
        $zipInfo['original_name'],
        $zipInfo['stored_name'],
        $zipInfo['private_path'],
        $zipInfo['file_size_bytes'],
        $zipInfo['checksum_sha256'],
        $now,
    ]);
    $fileId = (int)$pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO `project_versions` (`project_id`, `file_id`, `version`, `changelog`, `status`, `released_at`, `created_at`)
         VALUES (?, ?, ?, ?, 'published', ?, ?)"
    )->execute([$projectId, $fileId, $version, mb_substr($changelog, 0, 1500), $now, $now]);

    $pdo->prepare(
        "UPDATE `projects` SET `version` = ?, `file_size_bytes` = ?, `file_size_label` = ?, `last_updated_at` = ?, `updated_at` = ?
         WHERE `id` = ?"
    )->execute([$version, $zipInfo['file_size_bytes'], $zipInfo['file_size_label'], $now, $now, $projectId]);

    // Notify verified buyers holding active licenses
    $bStmt = $pdo->prepare(
        "SELECT DISTINCT o.buyer_id FROM `order_items` oi
         INNER JOIN `orders` o ON o.id = oi.order_id
         WHERE oi.project_id = ? AND o.status = 'paid' AND oi.status = 'active'"
    );
    $bStmt->execute([$projectId]);
    foreach ($bStmt->fetchAll(PDO::FETCH_COLUMN) as $buyerId) {
        NotificationService::send(
            (int)$buyerId,
            'project_update',
            "Update v{$version} ready: {$proj['title']}",
            "A new version of \"{$proj['title']}\" is ready to download from your Downloads Vault.",
            'buyer/downloads.php',
            'download'
        );
    }

    $pdo->commit();
    flash('success', "Version v{$version} uploaded and published for \"{$proj['title']}\".");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Project version update error: ' . $e->getMessage());
    flash('error', 'Failed to publish new release version.');
}

redirect(url('seller/project-updates.php?project=' . $projectId));
