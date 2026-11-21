<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    Auth::logout();
    start_secure_session();
    flash('success', 'You have been signed out safely.');
}

redirect(url('index.php'));
