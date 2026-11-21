<?php
declare(strict_types=1);

/**
 * Role-Based Access Control (RBAC) & Resource Ownership Policies
 * Public Buyer/Seller permissions and Private Administrator permissions are strictly separated.
 */

function is_admin(?array $user = null): bool
{
    if ($user !== null) {
        return ($user['role'] ?? '') === ROLE_ADMIN;
    }
    return current_admin() !== null;
}

function is_approved_seller(?array $user = null): bool
{
    $u = $user ?? current_user();
    if ($u === null) {
        return false;
    }
    return (int)($u['is_seller'] ?? 0) === 1 && ($u['seller_status'] ?? '') === 'approved';
}

function require_seller(): array
{
    $user = require_login();
    if (!is_approved_seller($user)) {
        flash('warning', 'Approved seller privileges are required to access the Seller Studio.');
        redirect(url('seller/become-seller.php'));
    }
    return $user;
}

function admin_exists(): bool
{
    return (int)db()->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn() > 0;
}

function require_admin(): array
{
    header('X-Robots-Tag: noindex, nofollow, noarchive');

    $admin = current_admin();
    if ($admin !== null && is_admin($admin)) {
        return $admin;
    }

    // If a regular buyer/seller tries navigating directly to /admin/*, block silently and send home
    $publicUser = current_user();
    if ($publicUser !== null) {
        log_security_event('unauthorized_admin_access', 'Blocked user access to ' . current_script_rel(), 'warning', (int)$publicUser['id']);
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Not found.'], 404);
        }
        redirect(url('index.php'));
    }

    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    $reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if ($reqUri !== '' && str_starts_with($reqUri, '/') && !str_starts_with($reqUri, '//') && str_contains($reqUri, '/admin/')) {
        $_SESSION['admin_intended_url'] = $reqUri;
    }
    redirect(url('admin.php'));
}

function can_manage_project(array $user, array $project): bool
{
    if (is_admin($user)) {
        return true;
    }
    return is_approved_seller($user) && (int)$project['seller_id'] === (int)$user['id'];
}

function user_has_purchased_project(int $userId, int $projectId): ?array
{
    $stmt = db()->prepare(
        "SELECT oi.*, o.order_number, o.status AS order_status, o.paid_at
         FROM `order_items` oi
         INNER JOIN `orders` o ON o.id = oi.order_id
         WHERE o.buyer_id = ? AND oi.project_id = ?
           AND o.status = 'paid' AND oi.status = 'active' AND oi.download_permitted = 1
         ORDER BY o.paid_at DESC, oi.id DESC
         LIMIT 1"
    );
    $stmt->execute([$userId, $projectId]);
    $row = $stmt->fetch();
    return $row ?: null;
}
