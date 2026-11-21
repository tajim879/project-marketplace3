<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/commissions.php'));
}

$admin = require_admin();
csrf_require();

$rate = (float)($_POST['default_commission_rate'] ?? 10);
$minW = (float)($_POST['min_withdrawal_amount'] ?? 500);
$maxW = (float)($_POST['max_withdrawal_amount'] ?? 100000);
$dlLimit = max(1, min(1000, (int)($_POST['default_download_limit'] ?? 25)));
$sellerAuto = !empty($_POST['seller_auto_approve']) ? '1' : '0';
$projAuto = !empty($_POST['project_auto_approve']) ? '1' : '0';

if ($rate < 0 || $rate > 50) {
    flash('error', 'Default commission rate must be between 0% and 50%.');
    redirect(url('admin/commissions.php'));
}
if ($minW < 50 || $maxW < $minW) {
    flash('error', 'Please enter valid minimum and maximum withdrawal thresholds.');
    redirect(url('admin/commissions.php'));
}

update_site_setting('default_commission_rate', number_format($rate, 2, '.', ''), 'finance');
update_site_setting('min_withdrawal_amount', number_format($minW, 2, '.', ''), 'finance');
update_site_setting('max_withdrawal_amount', number_format($maxW, 2, '.', ''), 'finance');
update_site_setting('default_download_limit', (string)$dlLimit, 'finance');
update_site_setting('seller_auto_approve', $sellerAuto, 'registration');
update_site_setting('project_auto_approve', $projAuto, 'registration');

log_admin_action((int)$admin['id'], 'update_commission_settings', 'setting', null, "Default commission set to {$rate}%, min withdrawal {$minW} BDT.");
flash('success', 'Commission and marketplace finance settings saved.');
redirect(url('admin/commissions.php'));
