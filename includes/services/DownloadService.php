<?php
declare(strict_types=1);

/**
 * Secure Download Service (PRD Section 12)
 * Strictly enforces: authenticated user, order ownership, Paid status,
 * download permission flag, optional download limit, and private path confinement.
 */
final class DownloadService
{
    public static function authorizeAndResolve(int $userId, int $orderItemId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT oi.*, o.buyer_id, o.status AS order_status, o.order_number,
                    p.slug AS project_slug, p.title AS project_title, p.version AS project_version, p.status AS project_status,
                    pf.id AS file_id, pf.version AS file_version, pf.original_name, pf.private_path,
                    pf.file_size_bytes, pf.is_inspected, pf.status AS file_status
             FROM `order_items` oi
             INNER JOIN `orders` o ON o.id = oi.order_id
             INNER JOIN `projects` p ON p.id = oi.project_id
             LEFT JOIN `project_files` pf ON pf.project_id = p.id AND pf.is_current = 1 AND pf.status = 'active'
             WHERE oi.id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderItemId]);
        $row = $stmt->fetch();

        if (!$row || (int)$row['buyer_id'] !== $userId) {
            log_security_event('unauthorized_download_attempt', "Blocked download for order item #{$orderItemId}", 'warning', $userId);
            return ['allowed' => false, 'code' => 403, 'message' => 'You do not have permission to download this project.'];
        }

        if ($row['order_status'] !== 'paid' || (int)$row['download_permitted'] !== 1 || $row['status'] !== 'active') {
            log_security_event('unpaid_download_blocked', "Blocked unpaid/revoked download on order {$row['order_number']}", 'warning', $userId);
            return ['allowed' => false, 'code' => 403, 'message' => 'Downloads are enabled strictly after order payment verification.'];
        }

        if ($row['download_limit'] !== null && (int)$row['download_count'] >= (int)$row['download_limit']) {
            return ['allowed' => false, 'code' => 429, 'message' => 'Maximum download count reached for this license. Contact support to reset.'];
        }

        if (empty($row['private_path']) || (int)$row['is_inspected'] !== 1) {
            return ['allowed' => false, 'code' => 404, 'message' => 'Verified project package is not currently available.'];
        }

        // Strict private directory path confinement (prevents any path traversal)
        $privateRoot = realpath(PRIVATE_PROJECTS_PATH);
        $fullPath = realpath(ROOT_PATH . '/' . ltrim((string)$row['private_path'], '/'));
        if (!$privateRoot || !$fullPath || !str_starts_with($fullPath, $privateRoot . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
            return ['allowed' => false, 'code' => 404, 'message' => 'Project archive could not be located in protected storage.'];
        }

        $safeDownloadName = slugify((string)$row['project_slug']) . '-v' . preg_replace('/[^0-9a-zA-Z._-]/', '', (string)$row['file_version']) . '.zip';

        return [
            'allowed' => true,
            'file_path' => $fullPath,
            'download_name' => $safeDownloadName,
            'record' => $row,
        ];
    }

    public static function recordDownload(int $userId, array $record): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO `downloads` (`user_id`, `project_id`, `order_id`, `order_item_id`, `file_id`, `version`, `ip_address`, `user_agent`, `status`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')"
            )->execute([
                $userId,
                $record['project_id'],
                $record['order_id'],
                $record['id'],
                $record['file_id'],
                $record['file_version'],
                client_ip(),
                mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
            ]);

            $pdo->prepare("UPDATE `order_items` SET `download_count` = `download_count` + 1 WHERE `id` = ?")
                ->execute([$record['id']]);

            $pdo->prepare("UPDATE `projects` SET `downloads_count` = `downloads_count` + 1 WHERE `id` = ?")
                ->execute([$record['project_id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Download log error: ' . $e->getMessage());
        }
    }

    /**
     * Allow project owner or marketplace administrator to securely retrieve a project's private ZIP.
     */
    public static function authorizeOwnerOrAdmin(array $user, int $projectId): array
    {
        $stmt = db()->prepare(
            "SELECT p.id, p.seller_id, p.slug, p.title, pf.version AS file_version, pf.private_path
             FROM `projects` p
             INNER JOIN `project_files` pf ON pf.project_id = p.id AND pf.is_current = 1
             WHERE p.id = ?
             LIMIT 1"
        );
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();

        if (!$row || (!is_admin($user) && (int)$row['seller_id'] !== (int)$user['id'])) {
            return ['allowed' => false, 'code' => 403, 'message' => 'Access denied.'];
        }

        $privateRoot = realpath(PRIVATE_PROJECTS_PATH);
        $fullPath = realpath(ROOT_PATH . '/' . ltrim((string)$row['private_path'], '/'));
        if (!$privateRoot || !$fullPath || !str_starts_with($fullPath, $privateRoot . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
            return ['allowed' => false, 'code' => 404, 'message' => 'Private ZIP file not found.'];
        }

        $safeName = slugify((string)$row['slug']) . '-v' . preg_replace('/[^0-9a-zA-Z._-]/', '', (string)$row['file_version']) . '.zip';
        return ['allowed' => true, 'file_path' => $fullPath, 'download_name' => $safeName];
    }
}
