<?php
declare(strict_types=1);

define('APP_VERSION', '1.0.0');
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
// Allow Render (or another container host) to mount persistent storage outside the deploy image.
$marketplaceStorageDir = trim((string)(getenv('MARKETPLACE_STORAGE_DIR') ?: ''));
define('STORAGE_PATH', $marketplaceStorageDir !== '' ? rtrim($marketplaceStorageDir, '/\\') : ROOT_PATH . '/storage');
define('PRIVATE_PROJECTS_PATH', STORAGE_PATH . '/projects');
define('THUMBNAILS_PATH', STORAGE_PATH . '/thumbnails');
define('SCREENSHOTS_PATH', STORAGE_PATH . '/screenshots');
define('PAYMENT_PROOFS_PATH', STORAGE_PATH . '/payment-proofs');
define('AVATARS_PATH', STORAGE_PATH . '/avatars');
define('PRIVATE_PATH', STORAGE_PATH . '/private');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('DATABASE_PATH', ROOT_PATH . '/database');

// Roles
define('ROLE_BUYER', 'buyer');
define('ROLE_SELLER', 'seller');
define('ROLE_ADMIN', 'admin');

// Security & pagination thresholds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('PASSWORD_RESET_EXPIRY_MINUTES', 60);
define('PROJECTS_PER_PAGE', 9);
