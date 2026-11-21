<?php
declare(strict_types=1);

/**
 * Portable Zero-Touch PDO SQLite Connection Manager
 *
 * - Stores the SQLite database strictly OUTSIDE the project directory so the
 *   codebase stays completely clean, portable, and non-web-accessible.
 * - Automatically creates the database file, schema, initial settings, and
 *   marketplace catalog on the very first request anywhere it is deployed.
 * - Uses file locking (flock) so concurrent first-boot requests never collide.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static ?string $resolvedPath = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('The PHP pdo_sqlite extension is required to run this application.');
        }

        $dbFile = self::databasePath();
        $dataDir = dirname($dbFile);
        $isNewFile = !is_file($dbFile) || (int)@filesize($dbFile) === 0;

        $pdo = self::openSqlite($dbFile);

        if ($isNewFile || !self::isSchemaInitialized($pdo)) {
            $lockFile = $dataDir . DIRECTORY_SEPARATOR . basename($dbFile) . '.init.lock';
            $lockHandle = @fopen($lockFile, 'c+');
            if ($lockHandle !== false) {
                @flock($lockHandle, LOCK_EX);
            }
            try {
                // Re-check inside exclusive lock in case a parallel request just finished bootstrapping
                if (!self::isSchemaInitialized($pdo)) {
                    require_once INCLUDES_PATH . '/classes/AutoBootstrap.php';
                    AutoBootstrap::provision($pdo);
                }
            } finally {
                if ($lockHandle !== false) {
                    @flock($lockHandle, LOCK_UN);
                    @fclose($lockHandle);
                }
            }
        } elseif (!is_file(ROOT_PATH . '/assets/images/placeholders/project-default.svg')) {
            // Self-heal runtime preview/archive assets if project was moved to a clean copy
            require_once INCLUDES_PATH . '/classes/AutoBootstrap.php';
            AutoBootstrap::ensureRuntimeAssets($pdo);
        }

        self::ensureCategoryMediaColumns($pdo);

        self::$pdo = $pdo;
        return self::$pdo;
    }

    /**
     * Adds category icon media columns to databases created before this feature.
     */
    private static function ensureCategoryMediaColumns(PDO $pdo): void
    {
        try {
            $cols = [];
            foreach ($pdo->query("PRAGMA table_info(`categories`)")->fetchAll() as $row) {
                $cols[(string)$row['name']] = true;
            }
            if (!$cols) {
                return;
            }
            if (!isset($cols['icon_type'])) {
                $pdo->exec("ALTER TABLE `categories` ADD COLUMN `icon_type` TEXT NOT NULL DEFAULT 'icon'");
            }
            if (!isset($cols['icon_image'])) {
                $pdo->exec("ALTER TABLE `categories` ADD COLUMN `icon_image` TEXT DEFAULT NULL");
            }
            if (!isset($cols['icon_svg'])) {
                $pdo->exec("ALTER TABLE `categories` ADD COLUMN `icon_svg` TEXT DEFAULT NULL");
            }
        } catch (Throwable) {
            // Never block page rendering because of a migration hiccup.
        }
    }

    /**
     * Resolve a writable private directory strictly OUTSIDE ROOT_PATH.
     * Supports Linux, macOS, Windows (XAMPP/Laragon), Docker, and shared hosting.
     */
    public static function databasePath(): string
    {
        if (self::$resolvedPath !== null) {
            return self::$resolvedPath;
        }

        $realRoot = rtrim(str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH), '/');
        $instanceId = substr(hash('sha256', $realRoot), 0, 12);
        $fileName = 'codevault-' . $instanceId . '.sqlite';

        // Optional explicit path override (must live outside ROOT_PATH)
        $explicitPath = trim((string)(getenv('MARKETPLACE_DB_PATH') ?: ''));
        if ($explicitPath !== '') {
            $dir = dirname($explicitPath);
            if (!self::isInsideProject($dir, $realRoot) && self::ensureProtectedDir($dir)) {
                self::$resolvedPath = $explicitPath;
                return self::$resolvedPath;
            }
        }

        // Candidate private directories outside the project root (in priority order)
        $candidates = [];

        $envDir = trim((string)(getenv('MARKETPLACE_DATA_DIR') ?: ''));
        if ($envDir !== '') {
            $candidates[] = $envDir;
        }

        // 1. Sibling private directory next to the project folder (e.g. /home/account/.codevault-data)
        $parentDir = dirname($realRoot);
        if ($parentDir !== '' && $parentDir !== $realRoot && $parentDir !== '/') {
            $candidates[] = $parentDir . DIRECTORY_SEPARATOR . '.codevault-data';
        }

        // 2. OS user application data directory (XDG / HOME / Windows LOCALAPPDATA)
        $xdg = trim((string)(getenv('XDG_DATA_HOME') ?: ''));
        if ($xdg !== '') {
            $candidates[] = rtrim($xdg, '/\\') . DIRECTORY_SEPARATOR . 'codevault-data';
        }
        $localAppData = trim((string)(getenv('LOCALAPPDATA') ?: getenv('APPDATA') ?: ''));
        if ($localAppData !== '') {
            $candidates[] = rtrim($localAppData, '/\\') . DIRECTORY_SEPARATOR . 'CodeVaultData';
        }
        $home = trim((string)(getenv('HOME') ?: ''));
        if ($home !== '') {
            $candidates[] = rtrim($home, '/\\') . DIRECTORY_SEPARATOR . '.codevault-data';
        }

        // 3. Universal OS temp directory fallback (works in read-only / open_basedir hosts)
        $candidates[] = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'codevault-data';

        foreach ($candidates as $dir) {
            if (self::isInsideProject($dir, $realRoot)) {
                continue;
            }
            if (self::ensureProtectedDir($dir)) {
                self::$resolvedPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
                return self::$resolvedPath;
            }
        }

        throw new RuntimeException('Unable to create an external writable directory for SQLite storage.');
    }

    private static function isInsideProject(string $candidateDir, string $normalizedRoot): bool
    {
        $norm = rtrim(str_replace('\\', '/', $candidateDir), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $norm = strtolower($norm);
            $normalizedRoot = strtolower($normalizedRoot);
        }
        return $norm === $normalizedRoot || str_starts_with($norm, $normalizedRoot . '/');
    }

    private static function ensureProtectedDir(string $dir): bool
    {
        if (!@is_dir($dir) && !@mkdir($dir, 0700, true) && !@is_dir($dir)) {
            return false;
        }
        if (!@is_writable($dir)) {
            return false;
        }

        // Probe actual file creation (guards against open_basedir / SELinux edge cases)
        $htFile = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!@is_file($htFile)) {
            $written = @file_put_contents(
                $htFile,
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
            );
            if ($written === false) {
                return false;
            }
        }

        $idxFile = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
        if (!@is_file($idxFile)) {
            @file_put_contents($idxFile, "<?php http_response_code(403); exit('Forbidden');\n");
        }

        return true;
    }

    private static function openSqlite(string $dbFile): PDO
    {
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        @chmod($dbFile, 0600);

        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');
        $pdo->exec('PRAGMA temp_store = MEMORY;');

        return $pdo;
    }

    private static function isSchemaInitialized(PDO $pdo): bool
    {
        try {
            $tbl = $pdo->query(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'site_settings' LIMIT 1"
            )->fetchColumn();
            if (!$tbl) {
                return false;
            }
            $flag = $pdo->query(
                "SELECT `setting_value` FROM `site_settings` WHERE `setting_key` = 'db_initialized' LIMIT 1"
            )->fetchColumn();
            return $flag === '1';
        } catch (Throwable) {
            return false;
        }
    }

    public static function reset(): void
    {
        self::$pdo = null;
        self::$resolvedPath = null;
    }
}

function db(): PDO
{
    return Database::connection();
}
