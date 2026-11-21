<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/categories.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$action = (string)($_POST['action'] ?? '');
$now = date('Y-m-d H:i:s');

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 100);
    $slug = slugify(trim((string)($_POST['slug'] ?? '')) ?: $name);
    $shortDesc = mb_substr(trim((string)($_POST['short_description'] ?? '')), 0, 200) ?: null;
    $desc = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 600) ?: $shortDesc;
    $iconKey = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string)($_POST['icon_name'] ?? 'code')))) ?: 'code';
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', trim((string)($_POST['accent_color'] ?? ''))) ? trim((string)$_POST['accent_color']) : '#2563EB';
    $sort = (int)($_POST['sort_order'] ?? 1);
    $isPop = !empty($_POST['is_popular']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string)$_POST['status'] : 'active';

    if ($name === '' || $slug === '') {
        flash('error', 'Category name is required.');
        redirect(url('admin/categories.php'));
    }

    // ---- Category visual: built-in icon / uploaded image / image URL / SVG code
    $iconType = (string)($_POST['icon_type'] ?? 'icon');
    if (!in_array($iconType, ['icon', 'image', 'url', 'svg'], true)) {
        $iconType = 'icon';
    }
    $prev = ['icon_image' => null, 'icon_svg' => null];
    if ($id > 0) {
        $ps = $pdo->prepare("SELECT `icon_image`, `icon_svg` FROM `categories` WHERE `id` = ?");
        $ps->execute([$id]);
        $prev = $ps->fetch() ?: $prev;
    }
    $iconImage = null;
    $iconSvg = null;

    if ($iconType === 'image') {
        $hasUpload = isset($_FILES['icon_file']) && (int)($_FILES['icon_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasUpload) {
            $up = UploadService::storeCategoryImage($_FILES['icon_file'], $slug);
            if (!$up['success']) {
                flash('error', (string)$up['message']);
                redirect(url('admin/categories.php' . ($id > 0 ? '?edit=' . $id : '')));
            }
            $iconImage = (string)$up['path'];
        } elseif (!empty($prev['icon_image']) && !preg_match('#^https?://#i', (string)$prev['icon_image'])) {
            $iconImage = (string)$prev['icon_image']; // keep existing uploaded file
        } else {
            flash('error', 'Please choose an image file to upload.');
            redirect(url('admin/categories.php' . ($id > 0 ? '?edit=' . $id : '')));
        }
    } elseif ($iconType === 'url') {
        $iconImage = safe_image_url((string)($_POST['icon_url'] ?? ''));
        if ($iconImage === '') {
            flash('error', 'Enter a valid image URL starting with http:// or https://');
            redirect(url('admin/categories.php' . ($id > 0 ? '?edit=' . $id : '')));
        }
    } elseif ($iconType === 'svg') {
        $iconSvg = sanitize_svg((string)($_POST['icon_svg'] ?? ''));
        if ($iconSvg === '') {
            flash('error', 'Paste valid SVG code that starts with <svg and ends with </svg>.');
            redirect(url('admin/categories.php' . ($id > 0 ? '?edit=' . $id : '')));
        }
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                "UPDATE `categories`
                 SET `name` = ?, `slug` = ?, `short_description` = ?, `description` = ?,
                     `icon_name` = ?, `icon_type` = ?, `icon_image` = ?, `icon_svg` = ?,
                     `accent_color` = ?, `sort_order` = ?, `is_popular` = ?,
                     `status` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([$name, $slug, $shortDesc, $desc, $iconKey, $iconType, $iconImage, $iconSvg, $accent, $sort, $isPop, $status, $now, $id]);
            log_admin_action($aid, 'update_category', 'category', $id, "Updated category {$name}.");
            flash('success', "Category \"{$name}\" updated.");
        } else {
            $pdo->prepare(
                "INSERT INTO `categories` (`name`, `slug`, `short_description`, `description`, `icon_name`, `icon_type`, `icon_image`, `icon_svg`, `accent_color`, `sort_order`, `is_popular`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$name, $slug, $shortDesc, $desc, $iconKey, $iconType, $iconImage, $iconSvg, $accent, $sort, $isPop, $status, $now, $now]);
            log_admin_action($aid, 'create_category', 'category', (int)$pdo->lastInsertId(), "Created category {$name}.");
            flash('success', "Category \"{$name}\" created.");
        }
    } catch (Throwable $e) {
        flash('error', 'A category with that slug already exists.');
    }
    redirect(url('admin/categories.php'));
}

if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE `categories` SET `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END, `updated_at` = ? WHERE `id` = ?")
        ->execute([$now, $id]);
    log_admin_action($aid, 'toggle_category', 'category', $id, 'Toggled category status.');
    flash('info', 'Category status updated.');
    redirect(url('admin/categories.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM `projects` WHERE `category_id` = ?");
    $cnt->execute([$id]);
    if ((int)$cnt->fetchColumn() > 0) {
        flash('error', 'Cannot delete a category that contains projects. Set it to inactive or move its projects first.');
    } else {
        $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?")->execute([$id]);
        log_admin_action($aid, 'delete_category', 'category', $id, 'Deleted empty category.');
        flash('info', 'Category deleted.');
    }
    redirect(url('admin/categories.php'));
}

redirect(url('admin/categories.php'));
