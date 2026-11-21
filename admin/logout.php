<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$admin = current_admin();
if ($admin && is_admin($admin)) {
    log_admin_action((int)$admin['id'], 'admin_logout', 'user', (int)$admin['id'], 'Signed out of Admin Panel.');
}
Auth::logoutAdmin();
flash('info', 'You have been signed out.');
redirect(url('admin.php'));
