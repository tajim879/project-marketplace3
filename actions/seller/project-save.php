<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('seller/projects.php'));
}

$user = current_admin() ?? require_seller();
$isAdminEditor = is_admin($user);
csrf_require();

$projectId = (int)($_POST['project_id'] ?? 0);
$isEdit = $projectId > 0;
$backUrl = $isEdit ? url('seller/edit-project.php?id=' . $projectId) : url('seller/upload-project.php');
if ($isAdminEditor && $isEdit) { $backUrl = url('seller/edit-project.php?id=' . $projectId); }

$pdo = db();
$existing = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    if (!$existing || !can_manage_project($user, $existing)) {
        flash('error', 'Project not found or access denied.');
        redirect($isAdminEditor ? url('admin/projects.php') : url('seller/projects.php'));
    }
}

$v = (new Validator($_POST))
    ->required('title', 'Project title')->minLength('title', 5, 'Project title')->maxLength('title', 160, 'Project title')
    ->required('category_id', 'Category')
    ->required('short_description', 'Short summary')->minLength('short_description', 15, 'Short summary')->maxLength('short_description', 280, 'Short summary')
    ->required('full_description', 'Full description')->minLength('full_description', 30, 'Full description')
    ->url('demo_url', 'Live demo URL')
    ->url('video_preview_url', 'Video preview URL')
    ->url('documentation_url', 'Documentation URL');

if ($v->fails()) {
    set_old_input($_POST);
    flash('error', $v->firstError());
    redirect($backUrl);
}

$catId = (int)$_POST['category_id'];
$cStmt = $pdo->prepare("SELECT `id` FROM `categories` WHERE `id` = ? AND `status` = 'active' LIMIT 1");
$cStmt->execute([$catId]);
if (!$cStmt->fetch()) {
    set_old_input($_POST);
    flash('error', 'Please choose a valid active category.');
    redirect($backUrl);
}

$pricing = ProjectService::parsePricing($_POST);
if (!$pricing['valid']) {
    set_old_input($_POST);
    flash('error', (string)$pricing['message']);
    redirect($backUrl);
}

$title = trim((string)$_POST['title']);
$slug = $isEdit ? (string)$existing['slug'] : ProjectService::uniqueSlug($title);
$version = preg_replace('/[^0-9a-zA-Z._-]/', '', trim((string)($_POST['version'] ?? ($existing['version'] ?? '1.0.0')))) ?: '1.0.0';
$compatibility = mb_substr(trim((string)($_POST['compatibility'] ?? '')), 0, 160) ?: null;
$supportMonths = max(0, min(36, (int)($_POST['support_months'] ?? 6)));
$docIncluded = !empty($_POST['documentation_included']) ? 1 : 0;
$saveAsDraft = ((string)($_POST['submit_mode'] ?? 'publish') === 'draft');
$autoApprove = site_setting('project_auto_approve', '0') === '1' || is_admin($user);

// Require ZIP file on create
$zipInfo = null;
if (!$isEdit) {
    $zipInfo = UploadService::storeProjectZip($_FILES['project_zip'] ?? [], $slug, $version);
    if (!$zipInfo['success']) {
        set_old_input($_POST);
        flash('error', (string)$zipInfo['message']);
        redirect($backUrl);
    }
}

// Thumbnail upload (required on create unless optional fallback, optional on edit)
$thumbnailPath = $existing['thumbnail'] ?? null;
if (!empty($_FILES['thumbnail']) && ($_FILES['thumbnail']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $th = UploadService::storeProjectThumbnail($_FILES['thumbnail'], $slug);
    if (!$th['success']) {
        set_old_input($_POST);
        flash('error', (string)$th['message']);
        redirect($backUrl);
    }
    $thumbnailPath = $th['path'];
} elseif (!$isEdit) {
    set_old_input($_POST);
    flash('error', 'Please upload a project cover thumbnail image (JPG, PNG, or WebP).');
    redirect($backUrl);
}

// Optional gallery screenshots (multiple files)
$savedScreens = [];
if (!empty($_FILES['screenshots']['name']) && is_array($_FILES['screenshots']['name'])) {
    $count = min(8, count($_FILES['screenshots']['name']));
    for ($i = 0; $i < $count; $i++) {
        $err = (int)($_FILES['screenshots']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $single = [
            'name' => $_FILES['screenshots']['name'][$i],
            'type' => $_FILES['screenshots']['type'][$i] ?? '',
            'tmp_name' => $_FILES['screenshots']['tmp_name'][$i] ?? '',
            'error' => $err,
            'size' => $_FILES['screenshots']['size'][$i] ?? 0,
        ];
        $sr = UploadService::storeProjectScreenshot($single, $slug, $i + 1);
        if (!$sr['success']) {
            set_old_input($_POST);
            flash('error', (string)$sr['message']);
            redirect($backUrl);
        }
        $savedScreens[] = $sr['path'];
    }
}

$now = date('Y-m-d H:i:s');
$pdo->beginTransaction();
try {
    if (!$isEdit) {
        $targetStatus = $saveAsDraft ? 'draft' : ($autoApprove ? 'approved' : 'pending');
        $pdo->prepare(
            "INSERT INTO `projects`
             (`seller_id`, `category_id`, `title`, `slug`, `short_description`, `description`,
              `thumbnail`, `demo_url`, `video_preview_url`, `documentation_url`, `version`,
              `file_size_bytes`, `file_size_label`, `compatibility`, `license_type`, `support_months`,
              `documentation_included`, `price`, `discount_price`, `extended_price`, `is_free`,
              `status`, `approved_by`, `approved_at`, `published_at`, `last_updated_at`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            (int)$user['id'],
            $catId,
            $title,
            $slug,
            trim((string)$_POST['short_description']),
            trim((string)$_POST['full_description']),
            $thumbnailPath,
            trim((string)($_POST['demo_url'] ?? '')) ?: null,
            trim((string)($_POST['video_preview_url'] ?? '')) ?: null,
            trim((string)($_POST['documentation_url'] ?? '')) ?: null,
            $version,
            $zipInfo['file_size_bytes'],
            $zipInfo['file_size_label'],
            $compatibility,
            $pricing['license_type'],
            $supportMonths,
            $docIncluded,
            $pricing['price'],
            $pricing['discount_price'],
            $pricing['extended_price'],
            $pricing['is_free'],
            $targetStatus,
            $targetStatus === 'approved' ? (int)$user['id'] : null,
            $targetStatus === 'approved' ? $now : null,
            $targetStatus === 'approved' ? $now : null,
            $now,
            $now,
            $now,
        ]);
        $projectId = (int)$pdo->lastInsertId();

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

        $changelog = trim((string)($_POST['changelog'] ?? '')) ?: 'Initial production release.';
        $pdo->prepare(
            "INSERT INTO `project_versions` (`project_id`, `file_id`, `version`, `changelog`, `status`, `released_at`, `created_at`)
             VALUES (?, ?, ?, ?, 'published', ?, ?)"
        )->execute([$projectId, $fileId, $version, mb_substr($changelog, 0, 1000), $now, $now]);

        if ($targetStatus === 'pending') {
            NotificationService::notifyAdmins(
                'project_pending',
                "New project submitted: {$title}",
                "@{$user['username']} uploaded \"{$title}\" for moderation review.",
                'admin/project-review.php?id=' . $projectId,
                'package'
            );
        }

        $msg = $targetStatus === 'draft'
            ? "Draft saved! You can finish editing and submit \"{$title}\" anytime."
            : ($targetStatus === 'approved'
                ? "Project \"{$title}\" is now live on the marketplace!"
                : "Project \"{$title}\" uploaded & submitted for admin moderation.");
    } else {
        $curStatus = (string)$existing['status'];
        if ($saveAsDraft) {
            $nextStatus = 'draft';
        } elseif (in_array($curStatus, ['draft', 'rejected'], true)) {
            $nextStatus = $autoApprove ? 'approved' : 'pending';
        } else {
            $nextStatus = $curStatus;
        }

        $pdo->prepare(
            "UPDATE `projects`
             SET `category_id` = ?, `title` = ?, `short_description` = ?, `description` = ?,
                 `thumbnail` = ?, `demo_url` = ?, `video_preview_url` = ?, `documentation_url` = ?,
                 `compatibility` = ?, `license_type` = ?, `support_months` = ?, `documentation_included` = ?,
                 `price` = ?, `discount_price` = ?, `extended_price` = ?, `is_free` = ?,
                 `status` = ?, `rejection_reason` = CASE WHEN ? = 'pending' OR ? = 'approved' THEN NULL ELSE `rejection_reason` END,
                 `published_at` = CASE WHEN ? = 'approved' THEN COALESCE(`published_at`, ?) ELSE `published_at` END,
                 `updated_at` = ?
             WHERE `id` = ?"
        )->execute([
            $catId,
            $title,
            trim((string)$_POST['short_description']),
            trim((string)$_POST['full_description']),
            $thumbnailPath,
            trim((string)($_POST['demo_url'] ?? '')) ?: null,
            trim((string)($_POST['video_preview_url'] ?? '')) ?: null,
            trim((string)($_POST['documentation_url'] ?? '')) ?: null,
            $compatibility,
            $pricing['license_type'],
            $supportMonths,
            $docIncluded,
            $pricing['price'],
            $pricing['discount_price'],
            $pricing['extended_price'],
            $pricing['is_free'],
            $nextStatus,
            $nextStatus,
            $nextStatus,
            $nextStatus,
            $now,
            $now,
            $projectId,
        ]);

        if ($nextStatus === 'pending' && $curStatus !== 'pending') {
            NotificationService::notifyAdmins(
                'project_resubmitted',
                "Project resubmitted: {$title}",
                "@{$user['username']} updated and submitted \"{$title}\" for review.",
                'admin/project-review.php?id=' . $projectId,
                'package'
            );
        }

        $msg = "Project \"{$title}\" updated successfully.";
    }

    ProjectService::syncFeaturesAndTech(
        $pdo,
        $projectId,
        (string)($_POST['features'] ?? ''),
        (string)($_POST['technologies'] ?? '')
    );

    if (!empty($savedScreens)) {
        $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(`sort_order`), 0) FROM `project_images` WHERE `project_id` = " . (int)$projectId)->fetchColumn();
        foreach ($savedScreens as $idx => $sPath) {
            $pdo->prepare(
                "INSERT INTO `project_images` (`project_id`, `image_path`, `caption`, `sort_order`, `status`, `created_at`)
                 VALUES (?, ?, ?, ?, 'active', ?)"
            )->execute([$projectId, $sPath, $title . ' screenshot ' . ($maxSort + $idx + 1), $maxSort + $idx + 1, $now]);
        }
    }

    $pdo->commit();
    flash('success', $msg);
    redirect($isAdminEditor ? url('admin/project-review.php?id=' . $projectId) : url('seller/projects.php'));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Seller project save error: ' . $e->getMessage());
    set_old_input($_POST);
    flash('error', 'Failed to save project. Please try again.');
    redirect($backUrl);
}
