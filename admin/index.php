<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

redirect(url(is_admin() ? 'admin/dashboard.php' : 'admin.php'));
