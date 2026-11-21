<?php
declare(strict_types=1);

/**
 * User & Seller Repository
 */
final class UserRepository
{
    public static function findSellerByUsername(string $username): ?array
    {
        $stmt = db()->prepare(
            "SELECT u.id, u.full_name, u.username, u.avatar, u.is_seller, u.seller_status,
                    u.is_verified_seller, u.created_at,
                    up.store_name, up.store_slug, up.headline, up.bio, up.skills,
                    up.country, up.city, up.website_url, up.github_url, up.portfolio_url,
                    up.response_time, up.seller_approved_at,
                    (SELECT COUNT(*) FROM `projects` p WHERE p.seller_id = u.id AND p.status = 'approved') AS approved_projects_count,
                    (SELECT COALESCE(SUM(p.sales_count), 0) FROM `projects` p WHERE p.seller_id = u.id AND p.status = 'approved') AS total_sales,
                    (SELECT COALESCE(ROUND(AVG(r.rating), 2), 0.00) FROM `reviews` r INNER JOIN `projects` p2 ON p2.id = r.project_id WHERE p2.seller_id = u.id AND r.status = 'published') AS avg_rating,
                    (SELECT COUNT(r.id) FROM `reviews` r INNER JOIN `projects` p3 ON p3.id = r.project_id WHERE p3.seller_id = u.id AND r.status = 'published') AS reviews_count
             FROM `users` u
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE (u.username = ? OR up.store_slug = ?)
               AND u.is_seller = 1 AND u.seller_status = 'approved' AND u.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function topRatedSellers(int $limit = 4): array
    {
        $sql = "SELECT u.id, u.full_name, u.username, u.avatar, u.is_verified_seller, u.created_at,
                       up.store_name, up.store_slug, up.headline, up.skills, up.city, up.country, up.response_time,
                       COUNT(DISTINCT p.id) AS projects_count,
                       COALESCE(SUM(p.sales_count), 0) AS total_sales,
                       COALESCE(
                           (SELECT ROUND(AVG(r.rating), 2) FROM `reviews` r
                            INNER JOIN `projects` px ON px.id = r.project_id
                            WHERE px.seller_id = u.id AND r.status = 'published'),
                           0.00
                       ) AS avg_rating,
                       (SELECT COUNT(r2.id) FROM `reviews` r2
                        INNER JOIN `projects` py ON py.id = r2.project_id
                        WHERE py.seller_id = u.id AND r2.status = 'published') AS reviews_count
                FROM `users` u
                LEFT JOIN `user_profiles` up ON up.user_id = u.id
                LEFT JOIN `projects` p ON p.seller_id = u.id AND p.status = 'approved'
                WHERE u.is_seller = 1 AND u.seller_status = 'approved' AND u.status = 'active'
                GROUP BY u.id
                ORDER BY avg_rating DESC, total_sales DESC, projects_count DESC
                LIMIT " . max(1, min(24, $limit));
        return db()->query($sql)->fetchAll();
    }

    public static function allApprovedSellersForFilter(): array
    {
        $sql = "SELECT u.username, COALESCE(NULLIF(up.store_name, ''), u.full_name) AS display_name,
                       COUNT(p.id) AS projects_count
                FROM `users` u
                LEFT JOIN `user_profiles` up ON up.user_id = u.id
                INNER JOIN `projects` p ON p.seller_id = u.id AND p.status = 'approved'
                WHERE u.is_seller = 1 AND u.seller_status = 'approved' AND u.status = 'active'
                GROUP BY u.id
                ORDER BY display_name ASC";
        return db()->query($sql)->fetchAll();
    }

    public static function sellerReviews(int $sellerId, int $limit = 12): array
    {
        $stmt = db()->prepare(
            "SELECT r.*, p.title AS project_title, p.slug AS project_slug,
                    b.full_name AS buyer_name, b.username AS buyer_username, b.avatar AS buyer_avatar
             FROM `reviews` r
             INNER JOIN `projects` p ON p.id = r.project_id
             INNER JOIN `users` b ON b.id = r.buyer_id
             WHERE p.seller_id = ? AND r.status = 'published'
             ORDER BY r.created_at DESC
             LIMIT " . max(1, min(50, $limit))
        );
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public static function buyerDashboardStats(int $userId): array
    {
        $pdo = db();

        $s1 = $pdo->prepare(
            "SELECT COUNT(oi.id) FROM `order_items` oi
             INNER JOIN `orders` o ON o.id = oi.order_id
             WHERE o.buyer_id = ? AND o.status = 'paid' AND oi.status = 'active'"
        );
        $s1->execute([$userId]);
        $purchases = (int)$s1->fetchColumn();

        $s2 = $pdo->prepare("SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ? AND `status` = 'completed'");
        $s2->execute([$userId]);
        $downloads = (int)$s2->fetchColumn();

        $s3 = $pdo->prepare("SELECT COUNT(*) FROM `wishlists` WHERE `user_id` = ?");
        $s3->execute([$userId]);
        $wishlist = (int)$s3->fetchColumn();

        $s4 = $pdo->prepare("SELECT COALESCE(SUM(`total_amount`), 0.00) FROM `orders` WHERE `buyer_id` = ? AND `status` = 'paid'");
        $s4->execute([$userId]);
        $totalSpent = (string)$s4->fetchColumn();

        $s5 = $pdo->prepare("SELECT COUNT(*) FROM `orders` WHERE `buyer_id` = ? AND `status` IN ('pending_payment','payment_submitted','under_verification')");
        $s5->execute([$userId]);
        $pendingOrders = (int)$s5->fetchColumn();

        $s6 = $pdo->prepare("SELECT COUNT(*) FROM `support_tickets` WHERE `user_id` = ? AND `status` IN ('open','in_progress','waiting')");
        $s6->execute([$userId]);
        $openTickets = (int)$s6->fetchColumn();

        return [
            'total_purchases' => $purchases,
            'total_downloads' => $downloads,
            'wishlist_count' => $wishlist,
            'total_spent' => $totalSpent,
            'pending_orders' => $pendingOrders,
            'open_tickets' => $openTickets,
        ];
    }
}
