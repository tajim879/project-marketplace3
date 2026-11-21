<?php
declare(strict_types=1);

/**
 * Seller Project Publishing, Version Updates & Admin Moderation Service
 */
final class ProjectService
{
    public static function uniqueSlug(string $title, ?int $ignoreProjectId = null): string
    {
        $base = slugify($title) ?: ('project-' . bin2hex(random_bytes(3)));
        $slug = $base;
        $i = 2;
        $pdo = db();
        while (true) {
            if ($ignoreProjectId) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `projects` WHERE `slug` = ? AND `id` <> ?");
                $stmt->execute([$slug, $ignoreProjectId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `projects` WHERE `slug` = ?");
                $stmt->execute([$slug]);
            }
            if ((int)$stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }

    public static function parsePricing(array $post): array
    {
        $isFree = !empty($post['is_free']) && (string)$post['is_free'] === '1';
        if ($isFree) {
            $lic = in_array((string)($post['license_type'] ?? ''), ['mit', 'free_personal'], true)
                ? (string)$post['license_type']
                : 'free_personal';
            return [
                'valid' => true,
                'is_free' => 1,
                'price' => '0.00',
                'discount_price' => null,
                'extended_price' => null,
                'license_type' => $lic,
            ];
        }

        $rawPrice = trim((string)($post['price'] ?? ''));
        if (!is_numeric($rawPrice) || (float)$rawPrice < 50 || (float)$rawPrice > 500000) {
            return ['valid' => false, 'message' => 'Regular price must be between ৳50 and ৳5,00,000 (or mark the project Free).'];
        }
        $price = number_format((float)$rawPrice, 2, '.', '');

        $discountPrice = null;
        $rawDisc = trim((string)($post['discount_price'] ?? ''));
        if ($rawDisc !== '') {
            if (!is_numeric($rawDisc) || (float)$rawDisc < 10) {
                return ['valid' => false, 'message' => 'Discount price must be a valid positive BDT amount.'];
            }
            $discountPrice = number_format((float)$rawDisc, 2, '.', '');
            if (bccomp($discountPrice, $price, 2) >= 0) {
                return ['valid' => false, 'message' => 'Discount price must be lower than the regular price.'];
            }
        }

        $extendedPrice = null;
        $rawExt = trim((string)($post['extended_price'] ?? ''));
        if ($rawExt !== '') {
            if (!is_numeric($rawExt) || bccomp(number_format((float)$rawExt, 2, '.', ''), $price, 2) <= 0) {
                return ['valid' => false, 'message' => 'Extended license price must be higher than the regular price.'];
            }
            $extendedPrice = number_format((float)$rawExt, 2, '.', '');
        } else {
            $extendedPrice = bcmul($price, '3.50', 2);
        }

        $lic = in_array((string)($post['license_type'] ?? ''), ['regular', 'extended', 'commercial'], true)
            ? (string)$post['license_type']
            : 'regular';

        return [
            'valid' => true,
            'is_free' => 0,
            'price' => $price,
            'discount_price' => $discountPrice,
            'extended_price' => $extendedPrice,
            'license_type' => $lic,
        ];
    }

    public static function syncFeaturesAndTech(PDO $pdo, int $projectId, string $rawFeatures, string $rawTech): void
    {
        $pdo->prepare("DELETE FROM `project_features` WHERE `project_id` = ?")->execute([$projectId]);
        $lines = preg_split('/\r\n|\r|\n/', $rawFeatures) ?: [];
        $sort = 1;
        foreach ($lines as $line) {
            $clean = trim($line, " \t\n\r\0\x0B-*•");
            if ($clean === '') {
                continue;
            }
            $pdo->prepare("INSERT INTO `project_features` (`project_id`, `feature_text`, `sort_order`) VALUES (?, ?, ?)")
                ->execute([$projectId, mb_substr($clean, 0, 220), $sort++]);
            if ($sort > 20) {
                break;
            }
        }

        $pdo->prepare("DELETE FROM `project_technologies` WHERE `project_id` = ?")->execute([$projectId]);
        $tags = preg_split('/[,;\n]+/', $rawTech) ?: [];
        $seen = [];
        foreach ($tags as $tag) {
            $name = trim($tag);
            $slug = slugify($name);
            if ($name === '' || $slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $pdo->prepare("INSERT INTO `project_technologies` (`project_id`, `tech_name`, `tech_slug`) VALUES (?, ?, ?)")
                ->execute([$projectId, mb_substr($name, 0, 50), mb_substr($slug, 0, 60)]);
            if (count($seen) >= 15) {
                break;
            }
        }
    }

    public static function approveProject(int $projectId, int $adminId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$projectId]);
        $proj = $stmt->fetch();
        if (!$proj) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $now = date('Y-m-d H:i:s');
        $pdo->prepare(
            "UPDATE `projects`
             SET `status` = 'approved', `rejection_reason` = NULL, `approved_by` = ?, `approved_at` = ?,
                 `published_at` = COALESCE(`published_at`, ?), `updated_at` = ?
             WHERE `id` = ?"
        )->execute([$adminId, $now, $now, $now, $projectId]);

        NotificationService::send(
            (int)$proj['seller_id'],
            'project_approved',
            'Project approved & live!',
            "\"{$proj['title']}\" has passed review and is now live on the marketplace.",
            'project.php?slug=' . urlencode((string)$proj['slug']),
            'check-circle'
        );

        log_admin_action($adminId, 'project_approved', 'project', $projectId, "Approved \"{$proj['title']}\"");
        return ['success' => true, 'message' => "\"{$proj['title']}\" is now approved and live."];
    }

    public static function rejectProject(int $projectId, int $adminId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason, 'UTF-8') < 5) {
            return ['success' => false, 'message' => 'Please enter a clear rejection reason for the seller.'];
        }

        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$projectId]);
        $proj = $stmt->fetch();
        if (!$proj) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $pdo->prepare(
            "UPDATE `projects` SET `status` = 'rejected', `rejection_reason` = ?, `updated_at` = ? WHERE `id` = ?"
        )->execute([mb_substr($reason, 0, 500), date('Y-m-d H:i:s'), $projectId]);

        NotificationService::send(
            (int)$proj['seller_id'],
            'project_rejected',
            'Changes requested on your project',
            "\"{$proj['title']}\" needs updates before publishing: {$reason}",
            'seller/edit-project.php?id=' . $projectId,
            'alert-circle'
        );

        log_admin_action($adminId, 'project_rejected', 'project', $projectId, "Rejected \"{$proj['title']}\": {$reason}");
        return ['success' => true, 'message' => "Project rejected and feedback sent to the seller."];
    }
}
