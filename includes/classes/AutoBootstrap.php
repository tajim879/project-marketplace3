<?php
declare(strict_types=1);

/**
 * Automatic Zero-Setup Production Database & Storage Provisioner
 *
 * Runs strictly once when the external SQLite database is created for the first time
 * anywhere the application is deployed. Provisions ONLY real platform structure:
 *   - Protected runtime directories & Apache deny guards
 *   - Default neutral project placeholder SVG
 *   - Normalized 48-table SQLite 3 schema (with FK & CHECK constraints)
 *   - Essential system settings, 14 product categories, bKash/Nagad/Rocket payment methods,
 *     navigation menus, homepage section definitions, FAQs, and legal/policy CMS pages.
 *
 * Zero demo accounts, zero demo projects, zero fake orders, and zero fake statistics are created.
 */
final class AutoBootstrap
{
    public static function isInitialized(PDO $pdo): bool
    {
        try {
            $tbl = $pdo->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'site_settings' LIMIT 1"
            )->fetchColumn();
            if (!$tbl) {
                return false;
            }
            $stmt = $pdo->prepare("SELECT `setting_value` FROM `site_settings` WHERE `setting_key` = 'db_initialized' LIMIT 1");
            $stmt->execute();
            return (string)$stmt->fetchColumn() === '1';
        } catch (Throwable) {
            return false;
        }
    }

    public static function ensureRuntimeAssets(): void
    {
        $publicDirs = [
            THUMBNAILS_PATH,
            SCREENSHOTS_PATH,
            AVATARS_PATH,
            STORAGE_PATH . '/banners',
            STORAGE_PATH . '/temp',
            ROOT_PATH . '/assets/images/placeholders',
            ROOT_PATH . '/assets/images/logo',
            ROOT_PATH . '/assets/images/payments',
        ];
        $privateDirs = [
            PRIVATE_PROJECTS_PATH,
            PAYMENT_PROOFS_PATH,
            PRIVATE_PATH,
            PRIVATE_PATH . '/ticket-attachments',
            LOGS_PATH,
        ];

        foreach (array_merge($publicDirs, $privateDirs) as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        $denyHtaccess = "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
        foreach ($privateDirs as $pdir) {
            $ht = $pdir . '/.htaccess';
            if (is_dir($pdir) && !is_file($ht)) {
                @file_put_contents($ht, $denyHtaccess);
            }
            $ix = $pdir . '/index.php';
            if (is_dir($pdir) && !is_file($ix)) {
                @file_put_contents($ix, "<?php http_response_code(403); exit('Forbidden');\n");
            }
        }

        $placeholder = ROOT_PATH . '/assets/images/placeholders/project-default.svg';
        if (!is_file($placeholder)) {
            @file_put_contents($placeholder, self::defaultPlaceholderSvg());
        }
    }

    public static function provision(PDO $pdo): void
    {
        self::initialize($pdo);
    }

    public static function initialize(PDO $pdo): void
    {
        self::ensureRuntimeAssets();

        $schemaFile = DATABASE_PATH . '/migrations/001_schema.sql';
        $seedFile = DATABASE_PATH . '/seeds/001_initial_data.sql';

        if (!is_file($schemaFile) || !is_file($seedFile)) {
            throw new RuntimeException('Database schema or system configuration seed file is missing.');
        }

        $pdo->exec((string)file_get_contents($schemaFile));

        $pdo->beginTransaction();
        try {
            $pdo->exec((string)file_get_contents($seedFile));

            self::seedOwnerAdmin($pdo);

            $pdo->prepare(
                "INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`)
                 VALUES ('system', 'db_initialized', '1')
                 ON CONFLICT(`setting_key`) DO UPDATE SET `setting_value` = '1'"
            )->execute();

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function seedOwnerAdmin(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');
        $targetEmail = 'maruf@gamile.com';

        $checkEmail = $pdo->prepare("SELECT `id`, `role` FROM `users` WHERE `email` = ? LIMIT 1");
        $checkEmail->execute([$targetEmail]);
        $found = $checkEmail->fetch();

        if ($found) {
            if (($found['role'] ?? '') !== 'admin') {
                $hash = password_hash('maruf@5539', PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE `users` SET `role` = 'admin', `password_hash` = ?, `is_seller` = 0, `seller_status` = 'none', `status` = 'active', `updated_at` = ? WHERE `id` = ?")
                    ->execute([$hash, $now, (int)$found['id']]);
            }
            return;
        }

        $hash = password_hash('maruf@5539', PASSWORD_BCRYPT);
        $uname = 'maruf';
        $uCheck = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
        $uCheck->execute([$uname]);
        if ((int)$uCheck->fetchColumn() > 0) {
            $uname = 'maruf_' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $pdo->prepare(
            "INSERT INTO `users`
             (`full_name`, `username`, `email`, `password_hash`, `role`, `is_seller`, `seller_status`, `is_verified_seller`, `email_verified_at`, `status`, `created_at`, `updated_at`)
             VALUES ('Maruf', ?, ?, ?, 'admin', 0, 'none', 0, ?, 'active', ?, ?)"
        )->execute([$uname, $targetEmail, $hash, $now, $now, $now]);

        $adminId = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT OR IGNORE INTO `user_profiles` (`user_id`, `headline`, `country`, `created_at`, `updated_at`)
             VALUES (?, 'Administrator', 'Bangladesh', ?, ?)"
        )->execute([$adminId, $now, $now]);

        $pdo->prepare(
            "INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`, `updated_at`)
             VALUES ('system', 'admin_bootstrapped', '1', ?)
             ON CONFLICT(`setting_key`) DO UPDATE SET `setting_value` = '1', `updated_at` = excluded.`updated_at`"
        )->execute([$now]);
    }

    private static function defaultPlaceholderSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="800" height="500">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#F6F7FB"/>
      <stop offset="100%" stop-color="#EBEEFA"/>
    </linearGradient>
    <linearGradient id="ac" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#635BFF"/>
      <stop offset="100%" stop-color="#8B7CFF"/>
    </linearGradient>
  </defs>
  <rect width="800" height="500" fill="url(#bg)"/>
  <rect x="80" y="70" width="640" height="360" rx="22" fill="#FFFFFF" stroke="#E4E7F2" stroke-width="2"/>
  <rect x="80" y="70" width="640" height="44" rx="22" fill="#F8F9FD"/>
  <circle cx="112" cy="92" r="6" fill="#E64B65" opacity="0.65"/>
  <circle cx="132" cy="92" r="6" fill="#F4A62A" opacity="0.65"/>
  <circle cx="152" cy="92" r="6" fill="#16B887" opacity="0.65"/>
  <rect x="348" y="196" width="104" height="104" rx="26" fill="url(#ac)" opacity="0.12"/>
  <polyline points="384 232 368 248 384 264" fill="none" stroke="#635BFF" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  <polyline points="416 232 432 248 416 264" fill="none" stroke="#635BFF" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  <line x1="406" y1="226" x2="394" y2="270" fill="none" stroke="#8B7CFF" stroke-width="4" stroke-linecap="round"/>
</svg>
SVG;
    }
}
