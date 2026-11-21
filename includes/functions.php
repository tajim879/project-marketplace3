<?php
declare(strict_types=1);

/**
 * Shared Database-Backed Settings, Navigation, Counters & Audit Helpers
 */

function all_site_settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }

    try {
        $stmt = db()->query("SELECT `setting_key`, `setting_value` FROM `site_settings`");
        $rows = $stmt->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }
    } catch (Throwable) {
        $cache = [];
    }

    return $cache;
}

function site_setting(string $key, string $default = ''): string
{
    $settings = all_site_settings();
    return array_key_exists($key, $settings) && $settings[$key] !== ''
        ? $settings[$key]
        : $default;
}

function get_menu_items(string $location): array
{
    static $cache = [];
    if (isset($cache[$location])) {
        return $cache[$location];
    }

    try {
        $stmt = db()->prepare(
            "SELECT mi.* FROM `menu_items` mi
             INNER JOIN `menus` m ON m.id = mi.menu_id
             WHERE m.location = ? AND m.status = 'active' AND mi.is_visible = 1
             ORDER BY mi.sort_order ASC, mi.id ASC"
        );
        $stmt->execute([$location]);
        $cache[$location] = $stmt->fetchAll();
    } catch (Throwable) {
        $cache[$location] = [];
    }

    return $cache[$location];
}

function global_categories(bool $onlyPopular = false, ?int $limit = null): array
{
    $sql = "SELECT c.*,
                COUNT(p.id) AS projects_count
            FROM `categories` c
            LEFT JOIN `projects` p ON p.category_id = c.id AND p.status = 'approved'
            WHERE c.status = 'active'";
    if ($onlyPopular) {
        $sql .= " AND c.is_popular = 1";
    }
    $sql .= " GROUP BY c.id ORDER BY c.sort_order ASC, c.name ASC";
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }
    return db()->query($sql)->fetchAll();
}

function marketplace_stats(): array
{
    static $stats = null;
    if ($stats !== null) {
        return $stats;
    }

    $pdo = db();
    $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM `projects` WHERE `status` = 'approved'")->fetchColumn();
    $totalSellers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `is_seller` = 1 AND `seller_status` = 'approved' AND `status` = 'active'")->fetchColumn();
    $totalBuyers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` IN ('buyer','seller') AND `status` = 'active'")->fetchColumn();
    $totalSales = (int)$pdo->query("SELECT COALESCE(SUM(`sales_count`), 0) FROM `projects` WHERE `status` = 'approved'")->fetchColumn();
    $avgRating = (string)$pdo->query("SELECT COALESCE(ROUND(AVG(`rating`), 2), '0.00') FROM `reviews` WHERE `status` = 'published'")->fetchColumn();

    $stats = [
        'total_projects' => $totalProjects,
        'total_sellers' => $totalSellers,
        'total_buyers' => $totalBuyers,
        'total_sales' => $totalSales,
        'avg_rating' => $avgRating,
    ];
    return $stats;
}

function user_wishlist_ids(?int $userId = null): array
{
    static $cache = [];
    $uid = $userId ?? (current_user_id() ?? 0);
    if ($uid <= 0) {
        return [];
    }
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }
    $stmt = db()->prepare("SELECT `project_id` FROM `wishlists` WHERE `user_id` = ?");
    $stmt->execute([$uid]);
    $cache[$uid] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    return $cache[$uid];
}

function user_wishlist_count(): int
{
    return count(user_wishlist_ids());
}

function active_cart_count(): int
{
    $uid = current_user_id();
    $sid = session_id();
    if ($uid) {
        $stmt = db()->prepare(
            "SELECT COUNT(ci.id) FROM `cart_items` ci
             INNER JOIN `carts` c ON c.id = ci.cart_id
             WHERE c.user_id = ? AND c.status = 'active'"
        );
        $stmt->execute([$uid]);
        return (int)$stmt->fetchColumn();
    }
    if ($sid !== '') {
        $stmt = db()->prepare(
            "SELECT COUNT(ci.id) FROM `cart_items` ci
             INNER JOIN `carts` c ON c.id = ci.cart_id
             WHERE c.user_id IS NULL AND c.session_id = ? AND c.status = 'active'"
        );
        $stmt->execute([$sid]);
        return (int)$stmt->fetchColumn();
    }
    return 0;
}

function unread_notifications_count(): int
{
    $uid = current_user_id();
    if (!$uid) {
        return 0;
    }
    $stmt = db()->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0");
    $stmt->execute([$uid]);
    return (int)$stmt->fetchColumn();
}

function recent_user_notifications(int $limit = 6): array
{
    $uid = current_user_id();
    if (!$uid) {
        return [];
    }
    $stmt = db()->prepare(
        "SELECT * FROM `notifications`
         WHERE `user_id` = ?
         ORDER BY `created_at` DESC
         LIMIT " . max(1, min(20, $limit))
    );
    $stmt->execute([$uid]);
    return $stmt->fetchAll();
}

function log_security_event(string $eventType, string $description, string $severity = 'info', ?int $userId = null): void
{
    try {
        $uid = $userId ?? current_user_id();
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
        $stmt = db()->prepare(
            "INSERT INTO `security_logs` (`user_id`, `event_type`, `severity`, `description`, `ip_address`, `user_agent`)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $eventType, $severity, mb_substr($description, 0, 250), client_ip(), $ua]);
    } catch (Throwable) {
        // Never break user response if log table unavailable
    }
}

function format_datetime(?string $datetime): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts !== false ? date('M j, Y · g:i A', $ts) : $datetime;
}

function generate_unique_code(string $prefix = 'ORD'): string
{
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function build_query_url(string $basePath, array $overrides = [], array $remove = []): string
{
    $params = $_GET;
    foreach ($remove as $key) {
        unset($params[$key]);
    }
    foreach ($overrides as $key => $val) {
        if ($val === null || $val === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $val;
        }
    }
    $qs = http_build_query($params);
    return url($basePath) . ($qs !== '' ? '?' . $qs : '');
}

function update_site_setting(string $key, string $value, string $group = 'general'): void
{
    db()->prepare(
        "INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`, `updated_at`)
         VALUES (?, ?, ?, ?)
         ON CONFLICT(`setting_key`) DO UPDATE SET
            `setting_value` = excluded.`setting_value`,
            `updated_at` = excluded.`updated_at`"
    )->execute([$group, $key, $value, date('Y-m-d H:i:s')]);
    all_site_settings(true);
}

function log_admin_action(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
    try {
        db()->prepare(
            "INSERT INTO `admin_logs` (`admin_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $adminId,
            mb_substr($action, 0, 120),
            $targetType,
            $targetId,
            $details !== null ? mb_substr($details, 0, 500) : null,
            client_ip(),
            date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable) {
    }
}

function seller_commission_rate(int $sellerId): string
{
    $stmt = db()->prepare("SELECT `custom_commission_rate` FROM `user_profiles` WHERE `user_id` = ? LIMIT 1");
    $stmt->execute([$sellerId]);
    $custom = $stmt->fetchColumn();
    if ($custom !== false && $custom !== null && $custom !== '' && is_numeric($custom)) {
        $val = (float)$custom;
        if ($val >= 0.0 && $val <= 90.0) {
            return number_format($val, 2, '.', '');
        }
    }
    $global = site_setting('default_commission_rate', '10.00');
    $g = is_numeric($global) ? max(0.0, min(90.0, (float)$global)) : 10.0;
    return number_format($g, 2, '.', '');
}

function enabled_payment_methods(): array
{
    return db()->query(
        "SELECT * FROM `payment_methods` WHERE `is_enabled` = 1 ORDER BY `sort_order` ASC, `id` ASC"
    )->fetchAll();
}

function all_payment_methods(): array
{
    return db()->query(
        "SELECT * FROM `payment_methods` ORDER BY `sort_order` ASC, `id` ASC"
    )->fetchAll();
}

function ensure_seller_balance(int $sellerId): array
{
    $pdo = db();
    $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$sellerId]);
    $stmt = $pdo->prepare("SELECT * FROM `seller_balances` WHERE `seller_id` = ? LIMIT 1");
    $stmt->execute([$sellerId]);
    return $stmt->fetch() ?: [
        'seller_id' => $sellerId,
        'gross_sales' => '0.00',
        'total_commission' => '0.00',
        'total_earnings' => '0.00',
        'available_balance' => '0.00',
        'pending_withdrawal' => '0.00',
        'withdrawn_total' => '0.00',
        'refunded_total' => '0.00',
    ];
}

function admin_pending_counts(): array
{
    static $counts = null;
    if ($counts !== null) {
        return $counts;
    }
    $pdo = db();
    $counts = [
        'payments' => (int)$pdo->query("SELECT COUNT(*) FROM `payments` WHERE `status` IN ('submitted','under_verification')")->fetchColumn(),
        'projects' => (int)$pdo->query("SELECT COUNT(*) FROM `projects` WHERE `status` = 'pending'")->fetchColumn(),
        'sellers' => (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `seller_status` = 'pending'")->fetchColumn(),
        'withdrawals' => (int)$pdo->query("SELECT COUNT(*) FROM `withdrawals` WHERE `status` IN ('pending','approved')")->fetchColumn(),
        'tickets' => (int)$pdo->query("SELECT COUNT(*) FROM `support_tickets` WHERE `status` IN ('open','in_progress')")->fetchColumn(),
        'reports' => (int)$pdo->query("SELECT COUNT(*) FROM `review_reports` WHERE `status` IN ('pending','open')")->fetchColumn(),
    ];
    return $counts;
}

