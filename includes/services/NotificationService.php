<?php
declare(strict_types=1);

/**
 * Notification Service
 */
final class NotificationService
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        string $iconName = 'bell'
    ): void {
        if (site_setting('notifications_enabled', '1') !== '1') {
            return;
        }
        $stmt = db()->prepare(
            "INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `action_url`, `icon_name`, `is_read`)
             VALUES (?, ?, ?, ?, ?, ?, 0)"
        );
        $stmt->execute([$userId, $type, mb_substr($title, 0, 180), $message, $actionUrl, $iconName]);
    }

    public static function notifyAdmins(
        string $type,
        string $title,
        string $message,
        ?string $actionPath = null,
        string $iconName = 'bell'
    ): void {
        $admins = db()->query("SELECT `id` FROM `users` WHERE `role` = 'admin' AND `status` = 'active'")->fetchAll(PDO::FETCH_COLUMN);
        $target = $actionPath ? url($actionPath) : null;
        foreach ($admins as $adminId) {
            self::send((int)$adminId, $type, $title, $message, $target, $iconName);
        }
    }

    public static function markRead(int $notificationId, int $userId): bool
    {
        $stmt = db()->prepare(
            "UPDATE `notifications` SET `is_read` = 1, `read_at` = ?
             WHERE `id` = ? AND `user_id` = ?"
        );
        $stmt->execute([date('Y-m-d H:i:s'), $notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function markAllRead(int $userId): int
    {
        $stmt = db()->prepare(
            "UPDATE `notifications` SET `is_read` = 1, `read_at` = ?
             WHERE `user_id` = ? AND `is_read` = 0"
        );
        $stmt->execute([date('Y-m-d H:i:s'), $userId]);
        return $stmt->rowCount();
    }

    public static function delete(int $notificationId, int $userId): bool
    {
        $stmt = db()->prepare("DELETE FROM `notifications` WHERE `id` = ? AND `user_id` = ?");
        $stmt->execute([$notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }
}
