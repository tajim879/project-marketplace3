<?php
declare(strict_types=1);

/**
 * Local Preview Router — mirrors Apache .htaccess private-directory protections
 * for PHP's built-in development server. Not invoked under Apache/Nginx.
 */
$uriPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$normalized = '/' . ltrim(str_replace('\\', '/', $uriPath), '/');

// Enforce identical access protection as .htaccess
$blockedPatterns = [
    '#^/storage/(projects|private|payment-proofs|logs)(/|$)#i',
    '#^/(database|includes|docs)(/|$)#i',
    '#^/\.#',
    '#\.(sql|sqlite|sqlite3|db|lock|log|env|ini|bak|sh)$#i',
    '#^/storage/(thumbnails|screenshots|avatars|banners|site-assets|temp)/.*\.php$#i',
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $normalized)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('403 Forbidden — Protected Marketplace Resource');
    }
}

$docRoot = dirname(__DIR__, 2);
$fullPath = realpath($docRoot . $normalized);

if ($fullPath !== false && str_starts_with($fullPath, $docRoot) && is_file($fullPath)) {
    // Serve static files directly (let PHP execute .php files normally)
    if (!str_ends_with(strtolower($fullPath), '.php')) {
        return false;
    }
    require $fullPath;
    return true;
}

if ($normalized === '/' || $normalized === '') {
    require $docRoot . '/index.php';
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo '404 Not Found';
return true;
