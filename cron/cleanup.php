<?php
declare(strict_types=1);

/**
 * Scheduled CLI Maintenance Utility (cron/cleanup.php)
 * Purges expired sessions, abandoned guest carts, stale login throttles,
 * old temp uploads, and checkpoints the external SQLite WAL journal.
 *
 * Crontab example (daily at 03:30):
 * 30 3 * * * /usr/bin/php /path/to/PROJECT-MARKETPLACE/cron/cleanup.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();
$throttles = $pdo->exec("DELETE FROM `login_attempts` WHERE `attempted_at` < datetime('now', '-7 days', 'localtime')");
$resets = $pdo->exec("UPDATE `password_resets` SET `status` = 'expired' WHERE `status` = 'pending' AND `expires_at` < datetime('now', 'localtime')");
$sessions = $pdo->exec("DELETE FROM `user_sessions` WHERE `status` <> 'active' AND `last_activity_at` < datetime('now', '-14 days', 'localtime')");
$carts = $pdo->exec("DELETE FROM `carts` WHERE `user_id` IS NULL AND `updated_at` < datetime('now', '-14 days', 'localtime')");
$coupons = $pdo->exec("UPDATE `coupons` SET `status` = 'expired' WHERE `status` = 'active' AND `expires_at` < datetime('now', 'localtime')");
$pdo->exec("PRAGMA wal_checkpoint(TRUNCATE)");

$tempDir = STORAGE_PATH . '/temp';
$tempRemoved = 0;
if (is_dir($tempDir)) {
    foreach (glob($tempDir . '/*') ?: [] as $file) {
        if (is_file($file) && basename($file) !== '.htaccess' && basename($file) !== 'index.php' && filemtime($file) < (time() - 86400)) {
            @unlink($file);
            $tempRemoved++;
        }
    }
}

echo sprintf(
    "[%s] Cleanup complete — throttles:%d, resets:%d, sessions:%d, carts:%d, coupons:%d, temp:%d\n",
    date('Y-m-d H:i:s'),
    (int)$throttles,
    (int)$resets,
    (int)$sessions,
    (int)$carts,
    (int)$coupons,
    $tempRemoved
);
