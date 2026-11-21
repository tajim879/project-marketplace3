<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
redirect(url(is_approved_seller() ? 'seller/dashboard.php' : 'seller/become-seller.php'));
