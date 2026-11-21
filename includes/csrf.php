<?php
declare(strict_types=1);

/**
 * CSRF Protection Layer
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate(?string $token = null): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    if ($token === null) {
        $token = $_POST['_csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
    }

    return is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
}

function csrf_require(): void
{
    if (!csrf_validate()) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => 'Security token expired. Please refresh the page and try again.'], 419);
        }
        flash('error', 'Security verification failed. Please submit the form again.');
        $back = $_SERVER['HTTP_REFERER'] ?? url('index.php');
        // Prevent open redirect: allow only same-host or relative paths
        $parsed = parse_url($back);
        $currentHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($parsed['host']) && strcasecmp($parsed['host'], $currentHost) !== 0) {
            $back = url('index.php');
        }
        redirect($back);
    }
}

function csrf_rotate(): void
{
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
