<?php
declare(strict_types=1);

/**
 * Auth Domain Class
 * Handles registration, rate-limited login, logout, session tracking,
 * password resets, and password updates with PDO transactions.
 */
final class Auth
{
    public static function isRateLimited(string $email, string $ip): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 60));
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM `login_attempts`
             WHERE (`ip_address` = ? OR `email` = ?)
               AND `status` = 'failed'
               AND `attempted_at` >= ?"
        );
        $stmt->execute([$ip, strtolower(trim($email)), $cutoff]);
        return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
    }

    public static function recordAttempt(string $email, string $ip, string $status): void
    {
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
        $stmt = db()->prepare(
            "INSERT INTO `login_attempts` (`email`, `ip_address`, `status`, `user_agent`)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([strtolower(trim($email)), $ip, $status, $ua]);
    }

    public static function attemptLogin(string $identifier, string $password): array
    {
        $ip = client_ip();
        $cleanId = trim($identifier);
        $normalizedEmail = strtolower($cleanId);

        if (self::isRateLimited($normalizedEmail, $ip)) {
            self::recordAttempt($normalizedEmail, $ip, 'blocked');
            log_security_event('login_rate_limited', "Rate limit triggered for {$cleanId}", 'warning');
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please wait ' . LOGIN_LOCKOUT_MINUTES . ' minutes before retrying.',
            ];
        }

        // Strictly restrict public login to buyer/seller accounts — admin auth is isolated
        $stmt = db()->prepare(
            "SELECT * FROM `users` WHERE (`email` = ? OR `username` = ?) AND `role` IN ('buyer', 'seller') LIMIT 1"
        );
        $stmt->execute([$normalizedEmail, $cleanId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            self::recordAttempt($normalizedEmail, $ip, 'failed');
            return [
                'success' => false,
                'message' => 'Invalid email/username or password.',
            ];
        }

        if ($user['status'] !== 'active') {
            log_security_event('inactive_login_blocked', "Blocked login for {$user['status']} account #{$user['id']}", 'warning', (int)$user['id']);
            return [
                'success' => false,
                'message' => 'Your account is currently ' . $user['status'] . '. Please contact marketplace support.',
            ];
        }

        // Rehash transparently if PHP password cost upgraded
        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $user['id']]);
        }

        self::establishSession($user);
        self::recordAttempt((string)$user['email'], $ip, 'success');
        log_security_event('login_success', 'Signed in successfully', 'info', (int)$user['id']);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Dedicated isolated Administrator authentication (used exclusively by /admin.php).
     */
    public static function attemptAdminLogin(string $email, string $password): array
    {
        $ip = client_ip();
        $cleanEmail = strtolower(trim($email));

        if (self::isRateLimited($cleanEmail, $ip)) {
            self::recordAttempt($cleanEmail, $ip, 'blocked');
            log_security_event('admin_login_rate_limited', "Admin login rate-limited for {$cleanEmail}", 'warning');
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please wait ' . LOGIN_LOCKOUT_MINUTES . ' minutes and try again.',
            ];
        }

        $stmt = db()->prepare(
            "SELECT * FROM `users` WHERE `email` = ? AND `role` = 'admin' LIMIT 1"
        );
        $stmt->execute([$cleanEmail]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, (string)$admin['password_hash'])) {
            self::recordAttempt($cleanEmail, $ip, 'failed');
            return [
                'success' => false,
                'message' => 'Invalid email address or password.',
            ];
        }

        if (($admin['status'] ?? '') !== 'active') {
            log_security_event('inactive_admin_blocked', "Blocked inactive admin #{$admin['id']}", 'warning', (int)$admin['id']);
            return [
                'success' => false,
                'message' => 'This account is currently disabled.',
            ];
        }

        if (password_needs_rehash((string)$admin['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $admin['id']]);
        }

        self::establishAdminSession($admin);
        self::recordAttempt((string)$admin['email'], $ip, 'success');
        log_security_event('admin_login_success', 'Administrator signed in', 'info', (int)$admin['id']);

        return ['success' => true, 'user' => $admin];
    }

    public static function establishAdminSession(array $admin): void
    {
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        csrf_rotate();

        $adminId = (int)$admin['id'];
        $sessionToken = bin2hex(random_bytes(32));
        $ip = client_ip();
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Browser'), 0, 480);

        $_SESSION['admin_user_id'] = $adminId;
        $_SESSION['admin_session_token'] = $sessionToken;
        $_SESSION['_last_regen'] = time();
        $_SESSION['_admin_touch'] = time();

        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE `users` SET `last_login_at` = ?, `last_login_ip` = ? WHERE `id` = ?")
            ->execute([$now, $ip, $adminId]);

        $pdo->prepare(
            "INSERT INTO `user_sessions` (`user_id`, `session_token`, `ip_address`, `user_agent`, `status`, `last_activity_at`)
             VALUES (?, ?, ?, ?, 'active', ?)"
        )->execute([$adminId, $sessionToken, $ip, $ua, $now]);
    }

    public static function establishSession(array $user): void
    {
        $guestSessionId = session_id();
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        csrf_rotate();

        $userId = (int)$user['id'];
        $sessionToken = bin2hex(random_bytes(32));
        $ip = client_ip();
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Browser'), 0, 480);

        $_SESSION['auth_user_id'] = $userId;
        $_SESSION['auth_session_token'] = $sessionToken;
        $_SESSION['_last_regen'] = time();
        $_SESSION['_session_touch'] = time();

        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE `users` SET `last_login_at` = ?, `last_login_ip` = ? WHERE `id` = ?")
            ->execute([$now, $ip, $userId]);

        $pdo->prepare(
            "INSERT INTO `user_sessions` (`user_id`, `session_token`, `ip_address`, `user_agent`, `status`, `last_activity_at`)
             VALUES (?, ?, ?, ?, 'active', ?)"
        )->execute([$userId, $sessionToken, $ip, $ua, $now]);

        // Merge any guest cart items cleanly into the user's cart
        if ($guestSessionId !== '') {
            OrderService::mergeGuestCart($guestSessionId, $userId);
        }
    }

    public static function register(array $data): array
    {
        if (site_setting('user_registration_enabled', '1') !== '1') {
            return ['success' => false, 'message' => 'New account registration is currently paused by the administrator.'];
        }

        $wantSeller = strtolower(trim((string)($data['account_type'] ?? 'buyer'))) === 'seller';
        if ($wantSeller && site_setting('seller_registration_enabled', '1') !== '1') {
            return ['success' => false, 'message' => 'New seller registration is currently paused by the administrator.'];
        }

        $pdo = db();
        $email = strtolower(trim((string)$data['email']));
        $username = strtolower(trim((string)$data['username']));
        $fullName = trim((string)$data['full_name']);
        $phone = trim((string)($data['phone'] ?? '')) ?: null;
        $password = (string)$data['password'];
        $now = date('Y-m-d H:i:s');

        $check = $pdo->prepare("SELECT `email`, `username` FROM `users` WHERE `email` = ? OR `username` = ? LIMIT 1");
        $check->execute([$email, $username]);
        if ($existing = $check->fetch()) {
            if (strcasecmp((string)$existing['email'], $email) === 0) {
                return ['success' => false, 'field' => 'email', 'message' => 'An account with this email already exists.'];
            }
            return ['success' => false, 'field' => 'username', 'message' => 'This username is already taken.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $autoApproveSeller = site_setting('seller_auto_approve', '1') === '1';

        $role = 'buyer';
        $isSeller = 0;
        $sellerStatus = 'none';
        if ($wantSeller) {
            if ($autoApproveSeller) {
                $role = 'seller';
                $isSeller = 1;
                $sellerStatus = 'approved';
            } else {
                $role = 'buyer';
                $isSeller = 0;
                $sellerStatus = 'pending';
            }
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                "INSERT INTO `users` (`full_name`, `username`, `email`, `phone`, `password_hash`, `role`, `is_seller`, `seller_status`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)"
            );
            $ins->execute([$fullName, $username, $email, $phone, $hash, $role, $isSeller, $sellerStatus, $now, $now]);
            $userId = (int)$pdo->lastInsertId();

            if ($wantSeller) {
                $storeName = trim((string)($data['store_name'] ?? '')) ?: ($fullName . ' Studio');
                $baseSlug = slugify($storeName) ?: $username;
                $storeSlug = $baseSlug;
                $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM `user_profiles` WHERE `store_slug` = ?");
                $slugCheck->execute([$storeSlug]);
                if ((int)$slugCheck->fetchColumn() > 0) {
                    $storeSlug = $baseSlug . '-' . $userId;
                }
                $headline = trim((string)($data['headline'] ?? '')) ?: 'Digital Product Creator';
                $payoutMethod = strtolower(trim((string)($data['payout_method'] ?? 'bkash')));
                if (!in_array($payoutMethod, ['bkash', 'nagad', 'rocket'], true)) {
                    $payoutMethod = 'bkash';
                }
                $payoutNumber = trim((string)($data['payout_number'] ?? '')) ?: $phone;

                $pdo->prepare(
                    "INSERT INTO `user_profiles`
                     (`user_id`, `store_name`, `store_slug`, `headline`, `payout_method`, `payout_number`,
                      `country`, `seller_applied_at`, `seller_approved_at`, `created_at`, `updated_at`)
                     VALUES (?, ?, ?, ?, ?, ?, 'Bangladesh', ?, ?, ?, ?)"
                )->execute([
                    $userId,
                    $storeName,
                    $storeSlug,
                    $headline,
                    $payoutMethod,
                    $payoutNumber,
                    $now,
                    $autoApproveSeller ? $now : null,
                    $now,
                    $now,
                ]);

                $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$userId]);

                NotificationService::send(
                    $userId,
                    'welcome_seller',
                    $autoApproveSeller ? 'Your Seller Studio is active!' : 'Seller application received',
                    $autoApproveSeller
                        ? 'Upload your digital projects, manage releases, and withdraw verified earnings via bKash, Nagad, or Rocket. You can also buy projects with this account.'
                        : 'Your account is active and your Seller Studio application is awaiting admin review.',
                    $autoApproveSeller ? 'seller/dashboard.php' : 'seller/become-seller.php',
                    'store'
                );

                NotificationService::notifyAdmins(
                    'new_seller',
                    $autoApproveSeller ? 'New seller joined' : 'New seller application pending',
                    "{$fullName} (@{$username}) registered seller store \"{$storeName}\".",
                    'admin/sellers.php',
                    'store'
                );
            } else {
                $pdo->prepare(
                    "INSERT INTO `user_profiles` (`user_id`, `country`, `created_at`, `updated_at`) VALUES (?, 'Bangladesh', ?, ?)"
                )->execute([$userId, $now, $now]);

                NotificationService::send(
                    $userId,
                    'welcome',
                    'Welcome to ' . site_setting('site_name', 'CodeVault'),
                    'Your buyer account is ready. Explore verified projects, save favorites, or open a Seller Studio anytime.',
                    'projects.php',
                    'sparkles'
                );

                NotificationService::notifyAdmins(
                    'new_user',
                    'New buyer registered',
                    "{$fullName} (@{$username}) created a buyer account.",
                    'admin/users.php',
                    'user-plus'
                );
            }

            log_security_event('account_registered', "Registered {$role} account @{$username}", 'info', $userId);

            $pdo->commit();

            $userStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();

            self::establishSession($user);
            return ['success' => true, 'user' => $user, 'seller_status' => $sellerStatus];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Registration error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not complete registration. Please try again.'];
        }
    }

    /**
     * One-time real Owner Admin account registration on /admin.php.
     * Permanently blocked the moment at least one administrator exists in SQLite.
     */
    public static function registerOwnerAdmin(array $data): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $existingAdmins = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();
            if ($existingAdmins > 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'An administrator account already exists. Please sign in.'];
            }

            $fullName = trim((string)$data['full_name']);
            $username = strtolower(trim((string)$data['username']));
            $email = strtolower(trim((string)$data['email']));
            $password = (string)$data['password'];
            $now = date('Y-m-d H:i:s');

            $dup = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ? OR `username` = ? LIMIT 1");
            $dup->execute([$email, $username]);
            if ($dup->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'That email or username is already registered.'];
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                "INSERT INTO `users`
                 (`full_name`, `username`, `email`, `password_hash`, `role`, `is_seller`, `seller_status`, `is_verified_seller`, `email_verified_at`, `status`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, 'admin', 1, 'approved', 1, ?, 'active', ?, ?)"
            )->execute([$fullName, $username, $email, $hash, $now, $now, $now]);
            $adminId = (int)$pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO `user_profiles` (`user_id`, `headline`, `country`, `seller_approved_at`, `created_at`, `updated_at`)
                 VALUES (?, 'Marketplace Administrator', 'Bangladesh', ?, ?, ?)"
            )->execute([$adminId, $now, $now, $now]);

            $pdo->prepare("INSERT OR IGNORE INTO `seller_balances` (`seller_id`) VALUES (?)")->execute([$adminId]);

            $pdo->prepare(
                "INSERT INTO `site_settings` (`setting_group`, `setting_key`, `setting_value`, `updated_at`)
                 VALUES ('system', 'admin_bootstrapped', '1', ?)
                 ON CONFLICT(`setting_key`) DO UPDATE SET `setting_value` = '1', `updated_at` = excluded.`updated_at`"
            )->execute([$now]);

            log_security_event('owner_admin_created', "Owner administrator @{$username} created", 'info', $adminId);
            log_admin_action($adminId, 'owner_admin_created', 'user', $adminId, "Initialized marketplace owner admin @{$username}");

            $pdo->commit();

            $uStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
            $uStmt->execute([$adminId]);
            $admin = $uStmt->fetch();
            self::establishAdminSession($admin);

            return ['success' => true, 'user' => $admin];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Owner admin setup error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not create administrator account. Please try again.'];
        }
    }

    public static function logoutAdmin(): void
    {
        $aid = current_admin_id();
        $token = $_SESSION['admin_session_token'] ?? null;

        if ($aid && is_string($token) && $token !== '') {
            try {
                db()->prepare("UPDATE `user_sessions` SET `status` = 'revoked' WHERE `session_token` = ? AND `user_id` = ?")
                    ->execute([$token, $aid]);
                log_security_event('admin_logout', 'Administrator signed out', 'info', $aid);
            } catch (Throwable) {
            }
        }

        unset($_SESSION['admin_user_id'], $_SESSION['admin_session_token'], $_SESSION['_admin_touch'], $_SESSION['admin_intended_url']);
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        csrf_rotate();
    }

    public static function logout(): void
    {
        $uid = current_user_id();
        $token = $_SESSION['auth_session_token'] ?? null;

        if ($uid && is_string($token) && $token !== '') {
            try {
                db()->prepare("UPDATE `user_sessions` SET `status` = 'revoked' WHERE `session_token` = ? AND `user_id` = ?")
                    ->execute([$token, $uid]);
                log_security_event('logout', 'Signed out cleanly', 'info', $uid);
            } catch (Throwable) {
            }
        }

        unset($_SESSION['auth_user_id'], $_SESSION['auth_session_token'], $_SESSION['_session_touch'], $_SESSION['intended_url']);
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        csrf_rotate();
    }

    public static function createPasswordReset(string $email): ?string
    {
        $pdo = db();
        $clean = strtolower(trim($email));
        $stmt = $pdo->prepare("SELECT `id`, `email`, `status` FROM `users` WHERE `email` = ? AND `role` IN ('buyer', 'seller') LIMIT 1");
        $stmt->execute([$clean]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        // Expire older pending tokens for this user
        $pdo->prepare("UPDATE `password_resets` SET `status` = 'expired' WHERE `user_id` = ? AND `status` = 'pending'")
            ->execute([$user['id']]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_EXPIRY_MINUTES * 60));

        $pdo->prepare(
            "INSERT INTO `password_resets` (`user_id`, `email`, `token_hash`, `status`, `ip_address`, `expires_at`)
             VALUES (?, ?, ?, 'pending', ?, ?)"
        )->execute([$user['id'], $user['email'], $tokenHash, client_ip(), $expiresAt]);

        log_security_event('password_reset_requested', 'Password reset token generated', 'info', (int)$user['id']);

        // Store last outbox record privately for shared hosts without SMTP configured
        $resetLink = absolute_url('reset-password.php?token=' . urlencode($rawToken) . '&email=' . urlencode((string)$user['email']));
        @mail(
            (string)$user['email'],
            site_setting('site_name', 'CodeVault') . ' — Password Reset Request',
            "Hello,\n\nA password reset was requested for your account. Open the link below within " . PASSWORD_RESET_EXPIRY_MINUTES . " minutes:\n{$resetLink}\n\nIf you did not request this, ignore this email.",
            'From: ' . site_setting('contact_email', 'noreply@codevault.market')
        );

        return $rawToken;
    }

    public static function verifyResetToken(string $email, string $rawToken): ?array
    {
        if ($email === '' || strlen($rawToken) < 32) {
            return null;
        }
        $hash = hash('sha256', $rawToken);
        $stmt = db()->prepare(
            "SELECT * FROM `password_resets`
             WHERE `email` = ? AND `token_hash` = ? AND `status` = 'pending' AND `expires_at` > ?
             LIMIT 1"
        );
        $stmt->execute([strtolower(trim($email)), $hash, date('Y-m-d H:i:s')]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function completePasswordReset(string $email, string $rawToken, string $newPassword): bool
    {
        $record = self::verifyResetToken($email, $rawToken);
        if (!$record) {
            return false;
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$hash, $record['user_id']]);

            $pdo->prepare("UPDATE `password_resets` SET `status` = 'used', `used_at` = ? WHERE `id` = ?")
                ->execute([date('Y-m-d H:i:s'), $record['id']]);

            $pdo->prepare("UPDATE `user_sessions` SET `status` = 'revoked' WHERE `user_id` = ?")
                ->execute([$record['user_id']]);

            log_security_event('password_reset_completed', 'Password reset successfully via token', 'info', (int)$record['user_id']);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Password reset error: ' . $e->getMessage());
            return false;
        }
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$userId]);
        $existingHash = $stmt->fetchColumn();

        if (!$existingHash || !password_verify($currentPassword, (string)$existingHash)) {
            log_security_event('password_change_failed', 'Incorrect current password supplied', 'warning', $userId);
            return ['success' => false, 'message' => 'Your current password is incorrect.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?")
            ->execute([$newHash, $userId]);

        // Revoke all other sessions except current
        $currToken = (string)($_SESSION['auth_session_token'] ?? '');
        $pdo->prepare("UPDATE `user_sessions` SET `status` = 'revoked' WHERE `user_id` = ? AND `session_token` <> ?")
            ->execute([$userId, $currToken]);

        log_security_event('password_changed', 'Account password updated and other sessions revoked', 'info', $userId);
        return ['success' => true, 'message' => 'Your password has been updated securely.'];
    }
}
