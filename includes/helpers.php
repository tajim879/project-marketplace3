<?php
declare(strict_types=1);

/**
 * Core Escaping, Formatting, Routing, Polyfills & Flash Helpers
 */

// Portable fallbacks when optional bcmath / mbstring PHP extensions are disabled on a host
if (!function_exists('bcadd')) {
    function _pm_scale_factor(int $scale): int { return (int)(10 ** max(0, min(6, $scale))); }
    function bcadd(string $a, string $b, int $scale = 0): string {
        $f = _pm_scale_factor($scale);
        $res = ((int)round((float)$a * $f) + (int)round((float)$b * $f)) / $f;
        return number_format($res, $scale, '.', '');
    }
    function bcsub(string $a, string $b, int $scale = 0): string {
        $f = _pm_scale_factor($scale);
        $res = ((int)round((float)$a * $f) - (int)round((float)$b * $f)) / $f;
        return number_format($res, $scale, '.', '');
    }
    function bcmul(string $a, string $b, int $scale = 0): string {
        return number_format((float)$a * (float)$b, $scale, '.', '');
    }
    function bcdiv(string $a, string $b, int $scale = 0): string {
        $div = (float)$b;
        return $div == 0.0 ? number_format(0, $scale, '.', '') : number_format((float)$a / $div, $scale, '.', '');
    }
    function bccomp(string $a, string $b, int $scale = 0): int {
        $f = _pm_scale_factor($scale);
        return (int)round((float)$a * $f) <=> (int)round((float)$b * $f);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s, ?string $enc = null): int {
        return preg_match_all('/./us', $s) ?: strlen($s);
    }
    function mb_substr(string $s, int $start, ?int $len = null, ?string $enc = null): string {
        preg_match_all('/./us', $s, $m);
        return implode('', array_slice($m[0] ?? [], $start, $len));
    }
    function mb_strtolower(string $s, ?string $enc = null): string {
        return strtolower($s);
    }
}

function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
}

/**
 * Determine application base path dynamically so root and subdirectory
 * deployments (Apache shared hosting or live preview) always resolve cleanly.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $appRoot = rtrim(str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH), '/');

    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $rel = substr($appRoot, strlen($docRoot));
        $base = $rel === '' ? '' : '/' . trim($rel, '/');
    } else {
        $base = '';
    }

    return $base;
}

function url(string $path = ''): string
{
    $clean = ltrim($path, '/');
    $bp = base_path();
    if ($clean === '') {
        return $bp === '' ? '/' : $bp . '/';
    }
    return ($bp === '' ? '' : $bp) . '/' . $clean;
}

function absolute_url(string $path = ''): string
{
    $scheme = is_https_request() ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Strip multiple forwarded hosts if any
    if (str_contains($host, ',')) {
        $host = trim(explode(',', $host)[0]);
    }
    return $scheme . '://' . $host . url($path);
}

function asset_url(string $path): string
{
    $clean = ltrim($path, '/');
    $base = url('assets/' . $clean);
    $diskPath = ROOT_PATH . '/assets/' . $clean;
    if (is_file($diskPath)) {
        $ver = (int)@filemtime($diskPath);
        if ($ver > 0) {
            return $base . '?v=' . $ver;
        }
    }
    return $base;
}

function media_url(?string $path, string $fallback = ''): string
{
    if ($path === null || trim($path) === '') {
        return $fallback !== '' ? url($fallback) : '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

function is_ajax_request(): bool
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    return str_contains($accept, 'application/json');
}

function redirect(string $target, int $status = 302): never
{
    header('Location: ' . $target, true, $status);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Format BDT / configured currency strictly from DECIMAL strings.
 */
function format_price(string|int|float|null $amount, bool $showFreeLabel = false): string
{
    $normalized = number_format((float)($amount ?? 0), 2, '.', '');
    if ($showFreeLabel && bccomp($normalized, '0.00', 2) === 0) {
        return 'Free';
    }
    $symbol = function_exists('site_setting') ? site_setting('currency_symbol', '৳') : '৳';
    [$intPart, $decPart] = explode('.', $normalized);
    $formattedInt = number_format((int)$intPart);
    return $decPart === '00'
        ? $symbol . $formattedInt
        : $symbol . $formattedInt . '.' . $decPart;
}

function effective_project_price(array $project, string $license = 'regular'): string
{
    if (!empty($project['is_free'])) {
        return '0.00';
    }
    if ($license === 'extended' && !empty($project['extended_price']) && bccomp((string)$project['extended_price'], '0.00', 2) > 0) {
        return number_format((float)$project['extended_price'], 2, '.', '');
    }
    if ($project['discount_price'] !== null && $project['discount_price'] !== '' && bccomp((string)$project['discount_price'], (string)$project['price'], 2) < 0) {
        return number_format((float)$project['discount_price'], 2, '.', '');
    }
    return number_format((float)$project['price'], 2, '.', '');
}

function discount_percentage(array $project): int
{
    if (!empty($project['is_free']) || $project['discount_price'] === null || $project['discount_price'] === '') {
        return 0;
    }
    $orig = (string)$project['price'];
    $disc = (string)$project['discount_price'];
    if (bccomp($orig, '0.00', 2) <= 0 || bccomp($disc, $orig, 2) >= 0) {
        return 0;
    }
    $diff = bcsub($orig, $disc, 4);
    $pct = bcmul(bcdiv($diff, $orig, 4), '100', 0);
    return max(1, (int)$pct);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . bin2hex(random_bytes(3));
}

function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (!$datetime) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($datetime))->format($format);
    } catch (Throwable) {
        return $datetime;
    }
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'Just now';
    }
    try {
        $dt = new DateTimeImmutable($datetime);
        $now = new DateTimeImmutable('now');
        $diff = max(0, $now->getTimestamp() - $dt->getTimestamp());
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $m = (int)floor($diff / 60);
            return $m . 'm ago';
        }
        if ($diff < 86400) {
            $h = (int)floor($diff / 3600);
            return $h . 'h ago';
        }
        if ($diff < 2592000) {
            $d = (int)floor($diff / 86400);
            return $d . 'd ago';
        }
        return $dt->format('M j, Y');
    } catch (Throwable) {
        return format_date($datetime);
    }
}

function format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 KB';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $pow = min((int)floor(log($bytes, 1024)), count($units) - 1);
    $val = $bytes / (1024 ** $pow);
    return number_format($val, $pow >= 2 ? 1 : 0) . ' ' . $units[$pow];
}

function client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ];
    foreach ($candidates as $raw) {
        if ($raw === '') {
            continue;
        }
        $ip = trim(explode(',', $raw)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return '127.0.0.1';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);
    return is_array($flashes) ? $flashes : [];
}

function set_old_input(array $input): void
{
    $safe = $input;
    unset($safe['password'], $safe['password_confirmation'], $safe['current_password'], $safe['_csrf_token']);
    $_SESSION['_old_input'] = $safe;
}

function old(string $key, string $default = ''): string
{
    $val = $_SESSION['_old_input'][$key] ?? $default;
    return is_scalar($val) ? (string)$val : $default;
}

function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'U';
    $second = isset($parts[1][0]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';
    return $first . $second;
}

function current_script_rel(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $bp = base_path();
    if ($bp !== '' && str_starts_with($script, $bp)) {
        $script = substr($script, strlen($bp));
    }
    return ltrim($script, '/');
}

function is_active_nav(string $target): bool
{
    $curr = current_script_rel();
    $clean = ltrim(parse_url($target, PHP_URL_PATH) ?: $target, '/');
    if ($clean === 'index.php' && ($curr === '' || $curr === 'index.php')) {
        return true;
    }
    return $curr === $clean;
}

function status_badge_meta(string $status): array
{
    $map = [
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'icon' => 'check-circle'],
        'approved' => ['label' => 'Approved', 'tone' => 'success', 'icon' => 'check-circle'],
        'active' => ['label' => 'Active', 'tone' => 'success', 'icon' => 'check-circle'],
        'published' => ['label' => 'Published', 'tone' => 'success', 'icon' => 'check-circle'],
        'resolved' => ['label' => 'Resolved', 'tone' => 'success', 'icon' => 'check-circle'],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'icon' => 'check-circle'],
        'pending_payment' => ['label' => 'Pending Payment', 'tone' => 'warning', 'icon' => 'clock'],
        'payment_submitted' => ['label' => 'Payment Submitted', 'tone' => 'primary', 'icon' => 'clock'],
        'submitted' => ['label' => 'Submitted', 'tone' => 'primary', 'icon' => 'clock'],
        'under_verification' => ['label' => 'Under Verification', 'tone' => 'primary', 'icon' => 'shield'],
        'verifying' => ['label' => 'Verifying', 'tone' => 'primary', 'icon' => 'shield'],
        'pending' => ['label' => 'Pending', 'tone' => 'warning', 'icon' => 'clock'],
        'open' => ['label' => 'Open', 'tone' => 'primary', 'icon' => 'message'],
        'in_progress' => ['label' => 'In Progress', 'tone' => 'primary', 'icon' => 'clock'],
        'waiting' => ['label' => 'Waiting Reply', 'tone' => 'warning', 'icon' => 'clock'],
        'closed' => ['label' => 'Closed', 'tone' => 'muted', 'icon' => 'check'],
        'draft' => ['label' => 'Draft', 'tone' => 'muted', 'icon' => 'file'],
        'rejected' => ['label' => 'Rejected', 'tone' => 'danger', 'icon' => 'x-circle'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'muted', 'icon' => 'x-circle'],
        'suspended' => ['label' => 'Suspended', 'tone' => 'danger', 'icon' => 'alert-circle'],
        'blocked' => ['label' => 'Blocked', 'tone' => 'danger', 'icon' => 'x-circle'],
        'revoked' => ['label' => 'Revoked', 'tone' => 'danger', 'icon' => 'x-circle'],
        'refund_requested' => ['label' => 'Refund Requested', 'tone' => 'warning', 'icon' => 'alert-circle'],
        'refunded' => ['label' => 'Refunded', 'tone' => 'muted', 'icon' => 'refresh'],
    ];

    $key = strtolower(trim($status));
    return $map[$key] ?? ['label' => ucwords(str_replace('_', ' ', $key)), 'tone' => 'muted', 'icon' => 'info'];
}

function render_status_badge(string $status): string
{
    $meta = status_badge_meta($status);
    return '<span class="pm-badge pm-badge-' . e($meta['tone']) . '">'
        . icon($meta['icon'], 13)
        . '<span>' . e($meta['label']) . '</span></span>';
}

function license_label(string $license): string
{
    return match ($license) {
        'regular' => 'Regular License',
        'extended' => 'Extended License',
        'mit' => 'MIT Open License',
        'commercial' => 'Commercial License',
        'free_personal' => 'Free Personal License',
        default => ucwords(str_replace('_', ' ', $license)),
    };
}
