<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/website-control.php'));
}

$admin = require_admin();
csrf_require();

$pdo = db();
$aid = (int)$admin['id'];
$section = (string)($_POST['section'] ?? 'general');
$now = date('Y-m-d H:i:s');

if ($section === 'general') {
    $fields = [
        'site_name' => ['general', 80],
        'site_tagline' => ['general', 140],
        'site_description' => ['general', 320],
        'contact_email' => ['general', 120],
        'contact_phone' => ['general', 40],
        'contact_address' => ['general', 220],
        'footer_about' => ['footer', 400],
        'copyright_text' => ['footer', 180],
        'meta_title' => ['seo', 160],
        'meta_description' => ['seo', 320],
        'github_url' => ['social', 220],
        'linkedin_url' => ['social', 220],
        'youtube_url' => ['social', 220],
    ];
    foreach ($fields as $key => [$grp, $maxLen]) {
        if (isset($_POST[$key])) {
            update_site_setting($key, mb_substr(trim((string)$_POST[$key]), 0, $maxLen), $grp);
        }
    }

    // Optional custom logo & favicon uploads
    if (!empty($_FILES['custom_logo']) && ($_FILES['custom_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $up = UploadService::storeBrandAsset($_FILES['custom_logo'], 'logo');
        if (!$up['success']) {
            flash('error', (string)$up['message']);
            redirect(url('admin/website-control.php?tab=general'));
        }
        update_site_setting('custom_logo_path', (string)$up['path'], 'branding');
    }
    if (!empty($_POST['reset_logo'])) {
        update_site_setting('custom_logo_path', '', 'branding');
    }

    if (!empty($_FILES['custom_favicon']) && ($_FILES['custom_favicon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $up = UploadService::storeBrandAsset($_FILES['custom_favicon'], 'favicon');
        if (!$up['success']) {
            flash('error', (string)$up['message']);
            redirect(url('admin/website-control.php?tab=general'));
        }
        update_site_setting('custom_favicon_path', (string)$up['path'], 'branding');
    }
    if (!empty($_POST['reset_favicon'])) {
        update_site_setting('custom_favicon_path', '', 'branding');
    }

    log_admin_action($aid, 'update_site_branding', 'setting', null, 'Updated site branding, SEO, footer, and contact settings.');
    flash('success', 'Branding, contact, SEO, and footer settings saved.');
    redirect(url('admin/website-control.php?tab=general'));
}

if ($section === 'homepage') {
    $heroKeys = [
        'hero_badge' => 100,
        'hero_title' => 180,
        'hero_subtitle' => 420,
        'hero_primary_cta_label' => 50,
        'hero_primary_cta_url' => 160,
        'hero_secondary_cta_label' => 50,
        'hero_secondary_cta_url' => 160,
    ];
    foreach ($heroKeys as $k => $max) {
        if (isset($_POST[$k])) {
            update_site_setting($k, mb_substr(trim((string)$_POST[$k]), 0, $max), 'homepage');
        }
    }
    update_site_setting('show_stats_bar', !empty($_POST['show_stats_bar']) ? '1' : '0', 'homepage');

    if (!empty($_POST['sections']) && is_array($_POST['sections'])) {
        $stmt = $pdo->prepare(
            "UPDATE `homepage_sections`
             SET `badge_text` = ?, `title` = ?, `subtitle` = ?, `sort_order` = ?, `items_limit` = ?, `is_visible` = ?, `updated_at` = ?
             WHERE `id` = ?"
        );
        foreach ($_POST['sections'] as $sid => $row) {
            if (!is_array($row)) {
                continue;
            }
            $stmt->execute([
                mb_substr(trim((string)($row['badge_text'] ?? '')), 0, 80) ?: null,
                mb_substr(trim((string)($row['title'] ?? 'Section')), 0, 140),
                mb_substr(trim((string)($row['subtitle'] ?? '')), 0, 260) ?: null,
                (int)($row['sort_order'] ?? 1),
                max(1, min(24, (int)($row['items_limit'] ?? 6))),
                !empty($row['is_visible']) ? 1 : 0,
                $now,
                (int)$sid,
            ]);
        }
    }

    log_admin_action($aid, 'update_homepage', 'homepage', null, 'Updated homepage hero and dynamic sections.');
    flash('success', 'Homepage hero and section controls updated.');
    redirect(url('admin/website-control.php?tab=homepage'));
}

if ($section === 'banner_save') {
    $id = (int)($_POST['id'] ?? 0);
    $pos = mb_substr(trim((string)($_POST['position'] ?? 'homepage_promo')), 0, 60) ?: 'homepage_promo';
    $badge = mb_substr(trim((string)($_POST['badge_text'] ?? '')), 0, 80) ?: null;
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 160);
    $sub = mb_substr(trim((string)($_POST['subtitle'] ?? '')), 0, 320) ?: null;
    $pLabel = mb_substr(trim((string)($_POST['primary_cta_label'] ?? '')), 0, 60) ?: null;
    $pUrl = mb_substr(trim((string)($_POST['primary_cta_url'] ?? '')), 0, 180) ?: null;
    $sLabel = mb_substr(trim((string)($_POST['secondary_cta_label'] ?? '')), 0, 60) ?: null;
    $sUrl = mb_substr(trim((string)($_POST['secondary_cta_url'] ?? '')), 0, 180) ?: null;
    $sort = (int)($_POST['sort_order'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string)$_POST['status'] : 'active';

    if ($title === '') {
        flash('error', 'Banner title is required.');
        redirect(url('admin/website-control.php?tab=banners'));
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                "UPDATE `banners`
                 SET `position` = ?, `badge_text` = ?, `title` = ?, `subtitle` = ?,
                     `primary_cta_label` = ?, `primary_cta_url` = ?, `secondary_cta_label` = ?, `secondary_cta_url` = ?,
                     `sort_order` = ?, `status` = ?, `updated_at` = ?
                 WHERE `id` = ?"
            )->execute([$pos, $badge, $title, $sub, $pLabel, $pUrl, $sLabel, $sUrl, $sort, $status, $now, $id]);
            log_admin_action($aid, 'update_banner', 'banner', $id, "Updated banner {$title}.");
        } else {
            $pdo->prepare(
                "INSERT INTO `banners`
                 (`position`, `badge_text`, `title`, `subtitle`, `primary_cta_label`, `primary_cta_url`,
                  `secondary_cta_label`, `secondary_cta_url`, `sort_order`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$pos, $badge, $title, $sub, $pLabel, $pUrl, $sLabel, $sUrl, $sort, $status, $now, $now]);
            log_admin_action($aid, 'create_banner', 'banner', (int)$pdo->lastInsertId(), "Created banner {$title}.");
        }
        flash('success', 'Promotional banner saved.');
    } catch (Throwable $e) {
        flash('error', 'Could not save banner (check duplicate title in same position).');
    }
    redirect(url('admin/website-control.php?tab=banners'));
}

if ($section === 'banner_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `banners` WHERE `id` = ?")->execute([$id]);
    log_admin_action($aid, 'delete_banner', 'banner', $id, 'Deleted promo banner.');
    flash('info', 'Banner removed.');
    redirect(url('admin/website-control.php?tab=banners'));
}

if ($section === 'menu_add') {
    $menuId = (int)($_POST['menu_id'] ?? 0);
    $label = mb_substr(trim((string)($_POST['label'] ?? '')), 0, 60);
    $linkUrl = mb_substr(trim((string)($_POST['url'] ?? '')), 0, 200);
    $sort = (int)($_POST['sort_order'] ?? 10);
    $target = ($_POST['target'] ?? '_self') === '_blank' ? '_blank' : '_self';

    if ($menuId > 0 && $label !== '' && $linkUrl !== '') {
        try {
            $pdo->prepare(
                "INSERT INTO `menu_items` (`menu_id`, `label`, `url`, `target`, `sort_order`, `is_visible`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, 1, ?)"
            )->execute([$menuId, $label, $linkUrl, $target, $sort, $now]);
            log_admin_action($aid, 'add_menu_item', 'menu_item', (int)$pdo->lastInsertId(), "Added menu link {$label}.");
            flash('success', "Menu link \"{$label}\" added.");
        } catch (Throwable $e) {
            flash('error', 'That URL already exists in the selected menu.');
        }
    }
    redirect(url('admin/website-control.php?tab=menus'));
}

if ($section === 'menu_toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE `menu_items` SET `is_visible` = CASE WHEN `is_visible` = 1 THEN 0 ELSE 1 END WHERE `id` = ?")->execute([$id]);
    flash('info', 'Menu item visibility toggled.');
    redirect(url('admin/website-control.php?tab=menus'));
}

if ($section === 'menu_delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM `menu_items` WHERE `id` = ?")->execute([$id]);
    log_admin_action($aid, 'delete_menu_item', 'menu_item', $id, 'Deleted menu link.');
    flash('info', 'Menu link removed.');
    redirect(url('admin/website-control.php?tab=menus'));
}

if ($section === 'system') {
    update_site_setting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0', 'general');
    update_site_setting('user_registration_enabled', !empty($_POST['user_registration_enabled']) ? '1' : '0', 'registration');
    update_site_setting('seller_registration_enabled', !empty($_POST['seller_registration_enabled']) ? '1' : '0', 'registration');
    update_site_setting('seller_auto_approve', !empty($_POST['seller_auto_approve']) ? '1' : '0', 'registration');
    update_site_setting('project_auto_approve', !empty($_POST['project_auto_approve']) ? '1' : '0', 'registration');
    update_site_setting('notifications_enabled', !empty($_POST['notifications_enabled']) ? '1' : '0', 'notifications');
    update_site_setting('newsletter_enabled', !empty($_POST['newsletter_enabled']) ? '1' : '0', 'notifications');
    update_site_setting('max_project_zip_mb', (string)max(5, min(500, (int)($_POST['max_project_zip_mb'] ?? 100))), 'upload');
    update_site_setting('max_image_mb', (string)max(1, min(25, (int)($_POST['max_image_mb'] ?? 5))), 'upload');
    update_site_setting('max_proof_mb', (string)max(1, min(25, (int)($_POST['max_proof_mb'] ?? 5))), 'upload');

    log_admin_action($aid, 'update_system_controls', 'setting', null, 'Updated maintenance, registration, and upload limit controls.');
    flash('success', 'System policies, maintenance mode, and upload limits saved.');
    redirect(url('admin/website-control.php?tab=system'));
}

redirect(url('admin/website-control.php'));
