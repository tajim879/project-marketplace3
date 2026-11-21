<?php
declare(strict_types=1);

/**
 * Secure Session Management & Strict User / Admin Session Isolation
 *
 * Public Buyer/Seller sessions (`auth_user_id`) and private Administrator sessions
 * (`admin_user_id`) are completely isolated so the public/user interface never
 * exposes administrator accounts, controls, or authentication pathways.
 */

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $cfg = app_config();
    $https = is_https_request();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name((string)($cfg['session_name'] ?? 'CODEVAULT_SID'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() ?: '/',
        'domain' => '',
        'secure' => $https,
        'samesite' => $https ? 'None' : 'Lax',
        'httponly' => true,
    ]);

    session_start();

    $now = time();
    if (!isset($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = $now;
    } elseif ($now - (int)$_SESSION['_last_regen'] > 1200) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = $now;
    }
}

function current_user_id(): ?int
{
    $id = $_SESSION['auth_user_id'] ?? null;
    return is_numeric($id) && (int)$id > 0 ? (int)$id : null;
}

function current_admin_id(): ?int
{
    $id = $_SESSION['admin_user_id'] ?? null;
    return is_numeric($id) && (int)$id > 0 ? (int)$id : null;
}

function current_user(bool $refresh = false): ?array
{
    static $cached = false;
    static $user = null;

    if ($cached && !$refresh) {
        return $user;
    }

    $uid = current_user_id();
    if ($uid === null) {
        $cached = true;
        $user = null;
        return null;
    }

    try {
        $stmt = db()->prepare(
            "SELECT u.*, up.store_name, up.store_slug, up.headline, up.bio, up.skills,
                    up.country, up.city, up.address, up.website_url, up.github_url,
                    up.portfolio_url, up.support_email, up.response_time,
                    up.payout_method, up.payout_number, up.seller_application_note,
                    up.seller_applied_at, up.seller_approved_at,
                    up.total_sales_count, up.average_rating, up.total_reviews_count
             FROM `users` u
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE u.id = ? AND u.role IN ('buyer', 'seller')
             LIMIT 1"
        );
        $stmt->execute([$uid]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active') {
            unset($_SESSION['auth_user_id'], $_SESSION['auth_session_token']);
            $cached = true;
            $user = null;
            return null;
        }

        if (!empty($_SESSION['auth_session_token']) && (time() - (int)($_SESSION['_session_touch'] ?? 0) > 120)) {
            $upd = db()->prepare("UPDATE `user_sessions` SET `last_activity_at` = ? WHERE `session_token` = ? AND `user_id` = ? AND `status` = 'active'");
            $upd->execute([date('Y-m-d H:i:s'), $_SESSION['auth_session_token'], $uid]);
            $_SESSION['_session_touch'] = time();
        }

        $cached = true;
        $user = $row;
        return $user;
    } catch (Throwable) {
        $cached = true;
        $user = null;
        return null;
    }
}

function current_admin(bool $refresh = false): ?array
{
    static $cached = false;
    static $admin = null;

    if ($cached && !$refresh) {
        return $admin;
    }

    $aid = current_admin_id();
    if ($aid === null) {
        $cached = true;
        $admin = null;
        return null;
    }

    try {
        $stmt = db()->prepare(
            "SELECT u.*, up.headline, up.country
             FROM `users` u
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE u.id = ? AND u.role = 'admin'
             LIMIT 1"
        );
        $stmt->execute([$aid]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active') {
            unset($_SESSION['admin_user_id'], $_SESSION['admin_session_token']);
            $cached = true;
            $admin = null;
            return null;
        }

        if (!empty($_SESSION['admin_session_token']) && (time() - (int)($_SESSION['_admin_touch'] ?? 0) > 120)) {
            $upd = db()->prepare("UPDATE `user_sessions` SET `last_activity_at` = ? WHERE `session_token` = ? AND `user_id` = ? AND `status` = 'active'");
            $upd->execute([date('Y-m-d H:i:s'), $_SESSION['admin_session_token'], $aid]);
            $_SESSION['_admin_touch'] = time();
        }

        $cached = true;
        $admin = $row;
        return $admin;
    } catch (Throwable) {
        $cached = true;
        $admin = null;
        return null;
    }
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(?string $returnTo = null): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }

    if (is_ajax_request()) {
        json_response(['success' => false, 'require_login' => true, 'message' => 'Please sign in to continue.'], 401);
    }

    $target = $returnTo ?? (string)($_SERVER['REQUEST_URI'] ?? '');
    if ($target !== '' && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
        $_SESSION['intended_url'] = $target;
    }
    flash('info', 'Please sign in to access your account.');
    redirect(url('login.php'));
}

function require_guest(): void
{
    $user = current_user();
    if ($user !== null) {
        redirect(url(is_approved_seller($user) ? 'seller/dashboard.php' : 'buyer/dashboard.php'));
    }
}
