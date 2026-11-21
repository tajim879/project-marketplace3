<?php
declare(strict_types=1);

/**
 * Notification Entity Helper
 */
final class Notification
{
    public static function forUser(int $userId, int $limit = 30, int $offset = 0, bool $unreadOnly = false): array
    {
        $sql = "SELECT * FROM `notifications` WHERE `user_id` = ?";
        if ($unreadOnly) {
            $sql .= " AND `is_read` = 0";
        }
        $sql .= " ORDER BY `created_at` DESC, `id` DESC LIMIT " . max(1, min(100, $limit)) . " OFFSET " . max(0, $offset);
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function countForUser(int $userId, bool $unreadOnly = false): int
    {
        $sql = "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ?";
        if ($unreadOnly) {
            $sql .= " AND `is_read` = 0";
        }
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
