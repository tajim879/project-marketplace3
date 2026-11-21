<?php
declare(strict_types=1);

/**
 * Project Repository - Full Search, Faceted Filtering, Sorting & Detail Queries
 */
final class ProjectRepository
{
    private static function baseSelect(): string
    {
        return "SELECT p.*,
                       c.name AS category_name, c.slug AS category_slug, c.icon_name AS category_icon, c.accent_color AS category_color,
                       u.full_name AS seller_name, u.username AS seller_username, u.avatar AS seller_avatar, u.is_verified_seller,
                       COALESCE(NULLIF(up.store_name, ''), u.full_name) AS seller_display_name,
                       CASE
                           WHEN p.is_free = 1 THEN 0.0
                           WHEN p.discount_price IS NOT NULL AND p.discount_price != '' AND CAST(p.discount_price AS REAL) < CAST(p.price AS REAL) THEN CAST(p.discount_price AS REAL)
                           ELSE CAST(p.price AS REAL)
                       END AS effective_price
                FROM `projects` p
                INNER JOIN `categories` c ON c.id = p.category_id
                INNER JOIN `users` u ON u.id = p.seller_id
                LEFT JOIN `user_profiles` up ON up.user_id = u.id";
    }

    /**
     * Search & filter approved projects across all PRD dimensions.
     */
    public static function search(array $filters = [], int $page = 1, int $perPage = PROJECTS_PER_PAGE): array
    {
        $where = ["p.status = 'approved'", "c.status = 'active'", "u.status = 'active'"];
        $params = [];

        // Text search by title, short description, full description, category, or technology
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $where[] = "(p.title LIKE ? OR p.short_description LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR EXISTS (SELECT 1 FROM `project_technologies` ptq WHERE ptq.project_id = p.id AND ptq.tech_name LIKE ?))";
            array_push($params, $like, $like, $like, $like, $like);
        }

        // Category filter (slug or ID)
        if (!empty($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $where[] = "p.category_id = ?";
                $params[] = (int)$filters['category'];
            } else {
                $where[] = "c.slug = ?";
                $params[] = (string)$filters['category'];
            }
        }

        // Free / Paid / Discounted filter
        $pricing = (string)($filters['pricing'] ?? 'all');
        if ($pricing === 'free') {
            $where[] = "p.is_free = 1";
        } elseif ($pricing === 'paid') {
            $where[] = "p.is_free = 0";
        } elseif ($pricing === 'discounted') {
            $where[] = "p.is_free = 0 AND p.discount_price IS NOT NULL AND p.discount_price != '' AND CAST(p.discount_price AS REAL) < CAST(p.price AS REAL)";
        }

        // Featured / Trending flags
        if (!empty($filters['featured'])) {
            $where[] = "p.is_featured = 1";
        }
        if (!empty($filters['trending'])) {
            $where[] = "p.is_trending = 1";
        }

        // Min / Max price on effective price
        $effectiveExpr = "(CASE WHEN p.is_free = 1 THEN 0.0 WHEN p.discount_price IS NOT NULL AND p.discount_price != '' AND CAST(p.discount_price AS REAL) < CAST(p.price AS REAL) THEN CAST(p.discount_price AS REAL) ELSE CAST(p.price AS REAL) END)";
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $min = max(0.0, (float)$filters['min_price']);
            $where[] = "{$effectiveExpr} >= ?";
            $params[] = $min;
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $max = max(0.0, (float)$filters['max_price']);
            $where[] = "{$effectiveExpr} <= ?";
            $params[] = $max;
        }

        // Minimum star rating
        if (isset($filters['rating']) && $filters['rating'] !== '' && is_numeric($filters['rating'])) {
            $minRating = max(1.0, min(5.0, (float)$filters['rating']));
            $where[] = "CAST(p.average_rating AS REAL) >= ?";
            $params[] = $minRating;
        }

        // Technology filter
        if (!empty($filters['tech'])) {
            $where[] = "EXISTS (SELECT 1 FROM `project_technologies` ptf WHERE ptf.project_id = p.id AND ptf.tech_slug = ?)";
            $params[] = slugify((string)$filters['tech']);
        }

        // Seller filter (username or ID)
        if (!empty($filters['seller'])) {
            if (is_numeric($filters['seller'])) {
                $where[] = "p.seller_id = ?";
                $params[] = (int)$filters['seller'];
            } else {
                $where[] = "u.username = ?";
                $params[] = (string)$filters['seller'];
            }
        }

        $whereSql = implode(' AND ', $where);

        // Count query
        $countSql = "SELECT COUNT(p.id)
                     FROM `projects` p
                     INNER JOIN `categories` c ON c.id = p.category_id
                     INNER JOIN `users` u ON u.id = p.seller_id
                     WHERE {$whereSql}";
        $countStmt = db()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // All 7 PRD sort modes
        $sort = (string)($filters['sort'] ?? 'latest');
        $orderBy = match ($sort) {
            'oldest' => 'p.published_at ASC, p.id ASC',
            'price_asc' => 'effective_price ASC, p.id DESC',
            'price_desc' => 'effective_price DESC, p.id DESC',
            'popular' => 'p.views_count DESC, p.wishlist_count DESC, p.sales_count DESC',
            'best_rated' => 'p.average_rating DESC, p.reviews_count DESC, p.sales_count DESC',
            'best_selling' => 'p.sales_count DESC, p.average_rating DESC',
            default => 'p.published_at DESC, p.id DESC',
        };

        $perPage = max(1, min(48, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($totalPages, $page));
        $offset = ($page - 1) * $perPage;

        $sql = self::baseSelect() . " WHERE {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $items = self::attachTechnologies($stmt->fetchAll());

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'sort' => $sort,
        ];
    }

    public static function attachTechnologies(array $projects): array
    {
        if (empty($projects)) {
            return [];
        }
        $ids = array_map(static fn(array $p): int => (int)$p['id'], $projects);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT `project_id`, `tech_name`, `tech_slug`
             FROM `project_technologies`
             WHERE `project_id` IN ({$placeholders})
             ORDER BY `id` ASC"
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['project_id']][] = $row;
        }
        foreach ($projects as &$proj) {
            $proj['technologies'] = $map[(int)$proj['id']] ?? [];
        }
        unset($proj);
        return $projects;
    }

    public static function homepageCollection(string $type, int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $filters = match ($type) {
            'featured' => ['featured' => 1, 'sort' => 'best_selling'],
            'trending' => ['trending' => 1, 'sort' => 'popular'],
            'latest' => ['sort' => 'latest'],
            'best_selling' => ['pricing' => 'paid', 'sort' => 'best_selling'],
            'free' => ['pricing' => 'free', 'sort' => 'popular'],
            default => ['sort' => 'latest'],
        };
        return self::search($filters, 1, $limit)['items'];
    }

    public static function findApprovedBySlug(string $slug): ?array
    {
        $sql = self::baseSelect() . ",
               up.headline AS seller_headline, up.response_time AS seller_response_time,
               (SELECT COALESCE(SUM(ps.sales_count), 0) FROM `projects` ps WHERE ps.seller_id = u.id AND ps.status = 'approved') AS seller_total_sales,
               (SELECT COALESCE(ROUND(AVG(rs.rating), 2), 0.00) FROM `reviews` rs INNER JOIN `projects` pr ON pr.id = rs.project_id WHERE pr.seller_id = u.id AND rs.status = 'published') AS seller_avg_rating,
               (SELECT COUNT(*) FROM `projects` pc WHERE pc.seller_id = u.id AND pc.status = 'approved') AS seller_projects_count
               FROM_PLACEHOLDER";

        // Build clean single query
        $stmt = db()->prepare(
            "SELECT p.*,
                    c.name AS category_name, c.slug AS category_slug, c.icon_name AS category_icon, c.accent_color AS category_color,
                    u.full_name AS seller_name, u.username AS seller_username, u.avatar AS seller_avatar,
                    u.is_verified_seller, u.created_at AS seller_joined_at,
                    COALESCE(NULLIF(up.store_name, ''), u.full_name) AS seller_display_name,
                    up.headline AS seller_headline, up.response_time AS seller_response_time,
                    (SELECT COALESCE(SUM(ps.sales_count), 0) FROM `projects` ps WHERE ps.seller_id = u.id AND ps.status = 'approved') AS seller_total_sales,
                    (SELECT COALESCE(ROUND(AVG(rs.rating), 2), 0.00) FROM `reviews` rs INNER JOIN `projects` pr ON pr.id = rs.project_id WHERE pr.seller_id = u.id AND rs.status = 'published') AS seller_avg_rating,
                    (SELECT COUNT(*) FROM `projects` pc WHERE pc.seller_id = u.id AND pc.status = 'approved') AS seller_projects_count
             FROM `projects` p
             INNER JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = p.seller_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE p.slug = ? AND p.status = 'approved' AND c.status = 'active' AND u.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $project = $stmt->fetch();
        if (!$project) {
            return null;
        }

        $pid = (int)$project['id'];

        $imgStmt = db()->prepare("SELECT * FROM `project_images` WHERE `project_id` = ? AND `status` = 'active' ORDER BY `sort_order` ASC, `id` ASC");
        $imgStmt->execute([$pid]);
        $project['images'] = $imgStmt->fetchAll();

        $featStmt = db()->prepare("SELECT `feature_text` FROM `project_features` WHERE `project_id` = ? ORDER BY `sort_order` ASC, `id` ASC");
        $featStmt->execute([$pid]);
        $project['features'] = $featStmt->fetchAll(PDO::FETCH_COLUMN);

        $techStmt = db()->prepare("SELECT `tech_name`, `tech_slug` FROM `project_technologies` WHERE `project_id` = ? ORDER BY `id` ASC");
        $techStmt->execute([$pid]);
        $project['technologies'] = $techStmt->fetchAll();

        $verStmt = db()->prepare("SELECT * FROM `project_versions` WHERE `project_id` = ? AND `status` = 'published' ORDER BY `released_at` DESC, `id` DESC");
        $verStmt->execute([$pid]);
        $project['versions'] = $verStmt->fetchAll();

        return $project;
    }

    public static function incrementViews(int $projectId): void
    {
        $seen = $_SESSION['_viewed_projects'] ?? [];
        if (in_array($projectId, $seen, true)) {
            return;
        }
        db()->prepare("UPDATE `projects` SET `views_count` = `views_count` + 1 WHERE `id` = ?")
            ->execute([$projectId]);
        $seen[] = $projectId;
        $_SESSION['_viewed_projects'] = array_slice($seen, -40);
    }

    public static function projectReviews(int $projectId): array
    {
        $stmt = db()->prepare(
            "SELECT r.*, u.full_name AS buyer_name, u.username AS buyer_username, u.avatar AS buyer_avatar,
                    oi.license_type
             FROM `reviews` r
             INNER JOIN `users` u ON u.id = r.buyer_id
             INNER JOIN `order_items` oi ON oi.id = r.order_item_id
             WHERE r.project_id = ? AND r.status = 'published'
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function ratingBreakdown(int $projectId): array
    {
        $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $stmt = db()->prepare(
            "SELECT `rating`, COUNT(*) AS c FROM `reviews`
             WHERE `project_id` = ? AND `status` = 'published'
             GROUP BY `rating`"
        );
        $stmt->execute([$projectId]);
        foreach ($stmt->fetchAll() as $row) {
            $r = (int)$row['rating'];
            if (isset($counts[$r])) {
                $counts[$r] = (int)$row['c'];
            }
        }
        return $counts;
    }

    public static function recalculateRating(int $projectId): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(ROUND(AVG(`rating`), 2), 0.00) AS avg_r
             FROM `reviews` WHERE `project_id` = ? AND `status` = 'published'"
        );
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();

        $pdo->prepare("UPDATE `projects` SET `reviews_count` = ?, `average_rating` = ? WHERE `id` = ?")
            ->execute([(int)$row['cnt'], $row['avg_r'], $projectId]);
    }

    public static function projectQuestions(int $projectId): array
    {
        $stmt = db()->prepare(
            "SELECT pq.*,
                    u.full_name AS asker_name, u.username AS asker_username, u.avatar AS asker_avatar,
                    a.full_name AS answerer_name, a.username AS answerer_username, a.is_verified_seller AS answerer_verified
             FROM `project_questions` pq
             INNER JOIN `users` u ON u.id = pq.user_id
             LEFT JOIN `users` a ON a.id = pq.answered_by
             WHERE pq.project_id = ? AND pq.status = 'visible'
             ORDER BY pq.created_at DESC
             LIMIT 30"
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function relatedProjects(int $projectId, int $categoryId, int $limit = 3): array
    {
        $sql = self::baseSelect() . "
               WHERE p.status = 'approved' AND p.category_id = ? AND p.id <> ?
               ORDER BY p.sales_count DESC, p.average_rating DESC
               LIMIT " . max(1, min(8, $limit));
        $stmt = db()->prepare($sql);
        $stmt->execute([$categoryId, $projectId]);
        return self::attachTechnologies($stmt->fetchAll());
    }

    public static function similarProjects(int $projectId, int $categoryId, array $techSlugs, int $limit = 3): array
    {
        if (empty($techSlugs)) {
            $sql = self::baseSelect() . "
                   WHERE p.status = 'approved' AND p.category_id <> ? AND p.id <> ?
                   ORDER BY p.is_featured DESC, p.average_rating DESC
                   LIMIT " . max(1, min(8, $limit));
            $stmt = db()->prepare($sql);
            $stmt->execute([$categoryId, $projectId]);
            return self::attachTechnologies($stmt->fetchAll());
        }

        $ph = implode(',', array_fill(0, count($techSlugs), '?'));
        $params = array_merge([$projectId, $categoryId], array_values($techSlugs));
        $sql = self::baseSelect() . "
               WHERE p.status = 'approved' AND p.id <> ? AND p.category_id <> ?
                 AND EXISTS (SELECT 1 FROM `project_technologies` pts WHERE pts.project_id = p.id AND pts.tech_slug IN ({$ph}))
               ORDER BY p.average_rating DESC, p.sales_count DESC
               LIMIT " . max(1, min(8, $limit));
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (count($rows) < $limit) {
            $exclude = array_merge([$projectId], array_map(static fn($r) => (int)$r['id'], $rows));
            $eph = implode(',', array_fill(0, count($exclude), '?'));
            $rem = $limit - count($rows);
            $fb = db()->prepare(
                self::baseSelect() . " WHERE p.status = 'approved' AND p.id NOT IN ({$eph})
                ORDER BY p.sales_count DESC LIMIT {$rem}"
            );
            $fb->execute($exclude);
            $rows = array_merge($rows, $fb->fetchAll());
        }

        return self::attachTechnologies($rows);
    }

    public static function popularTechnologies(int $limit = 18): array
    {
        $sql = "SELECT pt.tech_name, pt.tech_slug, COUNT(DISTINCT pt.project_id) AS projects_count
                FROM `project_technologies` pt
                INNER JOIN `projects` p ON p.id = pt.project_id AND p.status = 'approved'
                GROUP BY pt.tech_slug, pt.tech_name
                ORDER BY projects_count DESC, pt.tech_name ASC
                LIMIT " . max(1, min(40, $limit));
        return db()->query($sql)->fetchAll();
    }

    public static function featuredTestimonials(int $limit = 3): array
    {
        $stmt = db()->prepare(
            "SELECT r.*, p.title AS project_title, p.slug AS project_slug,
                    c.name AS category_name,
                    u.full_name AS buyer_name, u.username AS buyer_username, u.avatar AS buyer_avatar,
                    up.headline AS buyer_headline, up.city AS buyer_city
             FROM `reviews` r
             INNER JOIN `projects` p ON p.id = r.project_id AND p.status = 'approved'
             INNER JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = r.buyer_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE r.status = 'published' AND r.rating >= 4
             ORDER BY r.is_featured_testimonial DESC, r.rating DESC, r.created_at DESC
             LIMIT " . max(1, min(9, $limit))
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findCategoryBySlug(string $slug): ?array
    {
        $stmt = db()->prepare(
            "SELECT c.*,
                    COUNT(p.id) AS projects_count,
                    COALESCE(SUM(p.sales_count), 0) AS total_sales
             FROM `categories` c
             LEFT JOIN `projects` p ON p.category_id = c.id AND p.status = 'approved'
             WHERE c.slug = ? AND c.status = 'active'
             GROUP BY c.id
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function wishlistProjects(int $userId): array
    {
        $sql = self::baseSelect() . "
               INNER JOIN `wishlists` w ON w.project_id = p.id AND w.user_id = ?
               WHERE p.status = 'approved'
               ORDER BY w.created_at DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        return self::attachTechnologies($stmt->fetchAll());
    }
}
