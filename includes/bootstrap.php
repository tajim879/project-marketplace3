<?php
declare(strict_types=1);

/**
 * Application Bootstrap (Zero-Setup Portable Architecture)
 * Automatically prepares runtime directories, creates & initializes the external
 * SQLite database on first launch, starts a hardened session, and loads domain layers.
 */

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/config.php';

$cfg = app_config();
date_default_timezone_set((string)($cfg['timezone'] ?? 'Asia/Dhaka'));

// Production error hygiene: log internally, never leak raw stack traces
error_reporting(E_ALL);
ini_set('display_errors', !empty($cfg['app_debug']) ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0750, true);
}
if (is_dir(LOGS_PATH) && is_writable(LOGS_PATH)) {
    ini_set('error_log', LOGS_PATH . '/php-error.log');
}

// Security headers (compatible with Apache, Nginx, PHP server & live preview)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/classes/License.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/functions.php';

// Domain Classes, Repositories & Services
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Upload.php';
require_once __DIR__ . '/classes/Notification.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/ProjectRepository.php';
require_once __DIR__ . '/repositories/OrderRepository.php';
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/services/UploadService.php';
require_once __DIR__ . '/services/OrderService.php';
require_once __DIR__ . '/services/WalletService.php';
require_once __DIR__ . '/services/ProjectService.php';
require_once __DIR__ . '/services/DownloadService.php';

// Ensure external SQLite database is automatically created & ready on first run
db();

start_secure_session();

// Maintenance mode check for non-admin visitors (admin area & login always reachable)
$maintenance = site_setting('maintenance_mode', '0');
if ($maintenance === '1') {
    $script = current_script_rel();
    $exempt = ['admin.php', 'login.php', 'logout.php', 'actions/auth/login.php', 'actions/admin/auth.php'];
    if (!in_array($script, $exempt, true) && !str_starts_with($script, 'admin/') && !is_admin()) {
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Scheduled Maintenance</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F7F8FA;color:#0F172A;font-family:Inter,system-ui,sans-serif;padding:24px}.card{max-width:440px;background:#fff;border:1px solid #E4E7EC;border-radius:14px;padding:36px 28px;text-align:center;box-shadow:0 4px 16px rgba(15,23,42,.05)}h1{font-size:20px;margin:12px 0 8px}p{color:#64748B;line-height:1.6;font-size:14.5px;margin:0}</style></head><body><div class="card">' . icon('settings', 30) . '<h1>Scheduled Maintenance</h1><p>' . e(site_setting('site_name', 'CodeVault')) . ' is undergoing a brief scheduled upgrade. Please check back shortly.</p></div></body></html>';
        exit;
    }
}
