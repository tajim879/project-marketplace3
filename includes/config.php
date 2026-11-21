<?php
declare(strict_types=1);

/**
 * Zero-Configuration Portable Runtime Settings
 * No manual setup or config files required — works automatically on any host.
 */
function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $envTz = getenv('APP_TIMEZONE');
    $envDebug = getenv('APP_DEBUG');

    $config = [
        'app_env' => (string)(getenv('APP_ENV') ?: 'production'),
        'app_debug' => ($envDebug === '1' || strtolower((string)$envDebug) === 'true'),
        'timezone' => ($envTz !== false && $envTz !== '') ? $envTz : 'Asia/Dhaka',
        'session_name' => 'CODEVAULT_SID',
    ];

    return $config;
}
