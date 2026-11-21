<?php
declare(strict_types=1);

/**
 * Order & Purchase Repository
 */
final class OrderRepository
{
    public static function buyerOrders(int $buyerId, ?string $status = null, int $limit = 30): array
    {
        $sql = "SELECT o.*,
                       COUNT(oi.id) AS items_count,
                       (SELECT pm.name FROM `payments` py INNER JOIN `payment_methods` pm ON pm.id = py.payment_method_id WHERE py.order_id = o.id ORDER BY py.id DESC LIMIT 1) AS payment_method_name,
                       (SELECT py2.transaction_id FROM `payments` py2 WHERE py2.order_id = o.id ORDER BY py2.id DESC LIMIT 1) AS transaction_id,
                       (SELECT py3.status FROM `payments` py3 WHERE py3.order_id = o.id ORDER BY py3.id DESC LIMIT 1) AS payment_status
                FROM `orders` o
                LEFT JOIN `order_items` oi ON oi.order_id = o.id
                WHERE o.buyer_id = ?";
        $params = [$buyerId];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT " . max(1, min(100, $limit));
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findBuyerOrder(int $buyerId, string $orderNumberOrId): ?array
    {
        $stmt = db()->prepare(
            "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email, u.phone AS buyer_phone,
                    up.address AS buyer_address, up.city AS buyer_city, up.country AS buyer_country
             FROM `orders` o
             INNER JOIN `users` u ON u.id = o.buyer_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE o.buyer_id = ? AND (o.order_number = ? OR o.id = ?)
             LIMIT 1"
        );
        $stmt->execute([$buyerId, $orderNumberOrId, is_numeric($orderNumberOrId) ? (int)$orderNumberOrId : 0]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }

        $oid = (int)$order['id'];

        $itemStmt = db()->prepare(
            "SELECT oi.*, p.slug AS project_slug, p.thumbnail, p.version, p.file_size_label, p.is_free,
                    COALESCE(NULLIF(sup.store_name, ''), su.full_name) AS seller_name, su.username AS seller_username
             FROM `order_items` oi
             INNER JOIN `projects` p ON p.id = oi.project_id
             INNER JOIN `users` su ON su.id = oi.seller_id
             LEFT JOIN `user_profiles` sup ON sup.user_id = su.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );
        $itemStmt->execute([$oid]);
        $order['items'] = $itemStmt->fetchAll();

        $payStmt = db()->prepare(
            "SELECT py.*, pm.name AS method_name, pm.account_number AS receiver_account, pm.brand_color,
                    (SELECT pp.id FROM `payment_proofs` pp WHERE pp.payment_id = py.id ORDER BY pp.id DESC LIMIT 1) AS proof_id
             FROM `payments` py
             INNER JOIN `payment_methods` pm ON pm.id = py.payment_method_id
             WHERE py.order_id = ?
             ORDER BY py.id DESC"
        );
        $payStmt->execute([$oid]);
        $order['payments'] = $payStmt->fetchAll();

        $histStmt = db()->prepare(
            "SELECT osh.*, u.full_name AS actor_name
             FROM `order_status_history` osh
             LEFT JOIN `users` u ON u.id = osh.changed_by
             WHERE osh.order_id = ?
             ORDER BY osh.created_at ASC, osh.id ASC"
        );
        $histStmt->execute([$oid]);
        $order['timeline'] = $histStmt->fetchAll();

        return $order;
    }

    public static function findAnyOrder(string $orderNumberOrId): ?array
    {
        $stmt = db()->prepare(
            "SELECT o.*, u.full_name AS buyer_name, u.username AS buyer_username, u.email AS buyer_email, u.phone AS buyer_phone,
                    up.address AS buyer_address, up.city AS buyer_city, up.country AS buyer_country
             FROM `orders` o
             INNER JOIN `users` u ON u.id = o.buyer_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE o.order_number = ? OR o.id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderNumberOrId, is_numeric($orderNumberOrId) ? (int)$orderNumberOrId : 0]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        return self::findBuyerOrder((int)$order['buyer_id'], (string)$order['order_number']);
    }

    public static function buyerPurchasedItems(int $buyerId): array
    {
        $stmt = db()->prepare(
            "SELECT oi.*, o.order_number, o.status AS order_status, o.paid_at, o.created_at AS order_date,
                    p.slug AS project_slug, p.thumbnail, p.version AS current_version,
                    p.file_size_label, p.last_updated_at, p.is_free,
                    c.name AS category_name,
                    COALESCE(NULLIF(up.store_name, ''), u.full_name) AS seller_name, u.username AS seller_username,
                    r.id AS review_id, r.rating AS review_rating
             FROM `order_items` oi
             INNER JOIN `orders` o ON o.id = oi.order_id
             INNER JOIN `projects` p ON p.id = oi.project_id
             INNER JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = oi.seller_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             LEFT JOIN `reviews` r ON r.order_item_id = oi.id
             WHERE o.buyer_id = ?
             ORDER BY o.created_at DESC, oi.id DESC"
        );
        $stmt->execute([$buyerId]);
        return $stmt->fetchAll();
    }

    public static function buyerDownloadHistory(int $buyerId, int $limit = 40): array
    {
        $stmt = db()->prepare(
            "SELECT d.*, p.title AS project_title, p.slug AS project_slug, p.file_size_label,
                    o.order_number, oi.license_key, oi.license_type
             FROM `downloads` d
             INNER JOIN `projects` p ON p.id = d.project_id
             LEFT JOIN `orders` o ON o.id = d.order_id
             LEFT JOIN `order_items` oi ON oi.id = d.order_item_id
             WHERE d.user_id = ? AND d.status = 'completed'
             ORDER BY d.downloaded_at DESC
             LIMIT " . max(1, min(100, $limit))
        );
        $stmt->execute([$buyerId]);
        return $stmt->fetchAll();
    }

    public static function buyerPayments(int $buyerId): array
    {
        $stmt = db()->prepare(
            "SELECT py.*, pm.name AS method_name, pm.brand_color, pm.account_type,
                    o.order_number, o.status AS order_status
             FROM `payments` py
             INNER JOIN `payment_methods` pm ON pm.id = py.payment_method_id
             INNER JOIN `orders` o ON o.id = py.order_id
             WHERE py.buyer_id = ?
             ORDER BY py.submitted_at DESC"
        );
        $stmt->execute([$buyerId]);
        return $stmt->fetchAll();
    }
}
