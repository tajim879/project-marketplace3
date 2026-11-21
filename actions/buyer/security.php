<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('buyer/security.php'));
}

$user = require_login();
csrf_require();

$action = (string)($_POST['action'] ?? 'change_password');
$uid = (int)$user['id'];

if ($action === 'revoke_session') {
    $sid = (int)($_POST['session_id'] ?? 0);
    $currToken = (string)($_SESSION['auth_session_token'] ?? '');
    db()->prepare(
        "UPDATE `user_sessions` SET `status` = 'revoked'
         WHERE `id` = ? AND `user_id` = ? AND `session_token` <> ?"
    )->execute([$sid, $uid, $currToken]);
    log_security_event('session_revoked', "Revoked active session #{$sid}", 'info', $uid);
    flash('success', 'Selected browser session has been signed out.');
    redirect(url('buyer/security.php'));
}

$v = (new Validator($_POST))
    ->required('current_password', 'Current password')
    ->required('new_password', 'New password')->passwordStrength('new_password', 'New password')
    ->matches('new_password_confirmation', 'new_password', 'New password confirmation does not match.');

if ($v->fails()) {
    flash('error', $v->firstError());
    redirect(url('buyer/security.php'));
}

$res = Auth::changePassword($uid, (string)$_POST['current_password'], (string)$_POST['new_password']);
flash($res['success'] ? 'success' : 'error', $res['message']);
redirect(url('buyer/security.php'));
