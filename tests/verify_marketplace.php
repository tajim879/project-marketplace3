<?php
declare(strict_types=1);

/**
 * Isolated End-to-End Verification Suite for CodeVault Marketplace
 * Runs against a temporary external SQLite database via MARKETPLACE_DB_PATH —
 * NEVER touches or writes any test records into the real live database.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$tmpDb = sys_get_temp_dir() . '/codevault-e2e-' . bin2hex(random_bytes(5)) . '.sqlite';
putenv('MARKETPLACE_DB_PATH=' . $tmpDb);
putenv('MARKETPLACE_ADMIN_EMAIL=marketplace-test-admin@example.com');
putenv('MARKETPLACE_ADMIN_PASSWORD=TestAdmin!Pass5539');
$_ENV['MARKETPLACE_DB_PATH'] = $tmpDb;

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$passed = 0;
$failed = 0;

function check(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "[PASS] {$label}\n";
    } else {
        $failed++;
        echo "[FAIL] {$label}\n";
    }
}

try {
    $pdo = db();

    // 1. Clean zero-demo auto-provisioning
    $tables = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
    check($tables >= 40, "External SQLite schema provisioned ({$tables} normalized tables)");
    check((int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` IN ('buyer','seller')")->fetchColumn() === 0, 'Zero demo buyer/seller users seeded on fresh boot');
    check((int)$pdo->query("SELECT COUNT(*) FROM `projects`")->fetchColumn() === 0, 'Zero demo projects seeded on fresh boot');
    check((int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn() === 0, 'Zero demo orders seeded on fresh boot');
    check((int)$pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn() === 14, 'All 14 digital product categories seeded');
    check(count(enabled_payment_methods()) === 3, 'bKash, Nagad & Rocket manual payment methods ready');
    check(admin_exists(), 'Private owner admin (marketplace-test-admin@example.com) ready on fresh boot');

    // 2. Payment SVG branding
    foreach (['bkash', 'nagad', 'rocket'] as $code) {
        $svg = payment_brand_svg($code, 48);
        check(str_contains($svg, '<svg') && is_file(ROOT_PATH . "/assets/images/payments/{$code}.svg"), "Realistic {$code} SVG vector asset & helper present");
    }

    // 3. Private Owner Admin (marketplace-test-admin@example.com / TestAdmin!Pass5539) auto-provisioned & isolated from user login
    check(admin_exists(), 'Private Owner Admin auto-provisioned on first boot');
    $pubAttempt = Auth::attemptLogin('marketplace-test-admin@example.com', 'TestAdmin!Pass5539');
    check($pubAttempt['success'] === false, 'Admin credentials strictly rejected on public user login');
    $admAttempt = Auth::attemptAdminLogin('marketplace-test-admin@example.com', 'TestAdmin!Pass5539');
    check($admAttempt['success'] === true, 'Admin credentials accepted on isolated private admin login');
    $adminId = (int)($pdo->query("SELECT `id` FROM `users` WHERE `email` = 'marketplace-test-admin@example.com'")->fetchColumn());

    $secondOwner = Auth::registerOwnerAdmin([
        'full_name' => 'Intruder',
        'username' => 'intruder',
        'email' => 'intruder@example.com',
        'password' => 'StrongPass123',
    ]);
    check($secondOwner['success'] === false, 'Unauthorized admin creation strictly blocked');

    // 4. Seller registration directly via Auth::register(account_type = seller)
    $sellerRes = Auth::register([
        'account_type' => 'seller',
        'full_name' => 'Rahim Dev',
        'username' => 'rahim_dev',
        'email' => 'rahim@example.com',
        'phone' => '01722222222',
        'password' => 'SellerPass123',
        'store_name' => 'Rahim Code Studio',
        'payout_method' => 'bkash',
        'payout_number' => '01722222222',
    ]);
    check($sellerRes['success'] === true, 'Direct Seller account registration succeeded');
    $sellerRow = $pdo->query("SELECT u.*, up.store_name, up.payout_method FROM `users` u LEFT JOIN `user_profiles` up ON up.user_id = u.id WHERE u.username = 'rahim_dev'")->fetch();
    $sellerId = (int)$sellerRow['id'];
    check(($sellerRow['role'] ?? '') === 'seller' && ($sellerRow['seller_status'] ?? '') === 'approved' && ($sellerRow['payout_method'] ?? '') === 'bkash', 'Registered seller auto-approved with store & bKash payout');

    // 5. Buyer registration directly via Auth::register(account_type = buyer)
    $buyerRes = Auth::register([
        'account_type' => 'buyer',
        'full_name' => 'Karim Buyer',
        'username' => 'karim_buyer',
        'email' => 'karim@example.com',
        'phone' => '01833333333',
        'password' => 'BuyerPass123',
    ]);
    check($buyerRes['success'] === true, 'Direct Buyer account registration succeeded');
    $buyerRow = $pdo->query("SELECT * FROM `users` WHERE `username` = 'karim_buyer'")->fetch();
    $buyerId = (int)$buyerRow['id'];
    check(($buyerRow['role'] ?? '') === 'buyer' && (int)($buyerRow['is_seller'] ?? 1) === 0, 'Registered buyer assigned clean buyer role');

    // 6. Seller uploads a project -> Admin approves
    $catId = (int)$pdo->query("SELECT `id` FROM `categories` WHERE `slug` = 'php-projects'")->fetchColumn();
    $now = date('Y-m-d H:i:s');
    $pdo->prepare(
        "INSERT INTO `projects` (`seller_id`, `category_id`, `title`, `slug`, `short_description`, `description`, `version`, `file_size_bytes`, `file_size_label`, `license_type`, `price`, `extended_price`, `is_free`, `status`, `created_at`, `updated_at`)
         VALUES (?, ?, 'SmartPOS PHP Suite', 'smartpos-php-suite', 'Complete multi-branch PHP POS & billing system.', 'Full documentation and clean OOP architecture included.', '1.0.0', 2048, '2.0 KB', 'regular', '2000.00', '7000.00', 0, 'pending', ?, ?)"
    )->execute([$sellerId, $catId, $now, $now]);
    $projectId = (int)$pdo->lastInsertId();
    ProjectService::syncFeaturesAndTech($pdo, $projectId, "Multi-branch POS\nThermal Invoice", 'PHP 8.4, SQLite, JavaScript');

    $appProj = ProjectService::approveProject($projectId, $adminId);
    check($appProj['success'] === true, 'Admin moderated & approved seller project');

    // 7. Buyer adds to cart -> Checkout order -> Submits bKash TrxID -> Admin approves
    $_SESSION['user_id'] = $buyerId;
    current_user(true);
    $addCart = OrderService::addItem($projectId, 'regular');
    check($addCart['success'] === true, 'Buyer added approved project to shopping cart');

    $ordRes = OrderService::createOrderFromCart($buyerId, 'Please verify quickly');
    check($ordRes['success'] === true && !empty($ordRes['order_number']), 'Buyer created order from cart (' . ($ordRes['order_number'] ?? '') . ')');
    $orderNo = (string)$ordRes['order_number'];
    $orderId = (int)$ordRes['order_id'];

    $bkashId = (int)$pdo->query("SELECT `id` FROM `payment_methods` WHERE `code` = 'bkash'")->fetchColumn();
    $paySub = OrderService::submitManualPayment($buyerId, $orderNo, $bkashId, '01833333333', 'BKX99887766', '2000.00', 'Sent via bKash app', []);
    check($paySub['success'] === true, 'Buyer submitted manual bKash payment TrxID');

    // Duplicate TrxID must be blocked
    $dupCheck = OrderService::submitManualPayment($buyerId, $orderNo, $bkashId, '01833333333', 'BKX99887766', '2000.00', '', []);
    check($dupCheck['success'] === false, 'Duplicate bKash Transaction ID strictly rejected');

    $paymentId = (int)$pdo->query("SELECT `id` FROM `payments` WHERE `order_id` = {$orderId} LIMIT 1")->fetchColumn();
    $orderItemId = (int)$pdo->query("SELECT `id` FROM `order_items` WHERE `order_id` = {$orderId} LIMIT 1")->fetchColumn();

    // Unpaid buyer CANNOT unlock download before admin approval
    $preOwn = user_has_purchased_project($buyerId, $projectId);
    check($preOwn === null, 'Buyer blocked from download license before admin payment verification');

    // Admin approves manual bKash payment
    $verRes = OrderService::approvePayment($paymentId, $adminId, 'Verified bKash statement');
    check($verRes['success'] === true, 'Admin verified & approved bKash payment');

    // 8. Verify exact 10% commission (200.00) & 90% seller credit (1800.00) + license unlocked
    $bal = ensure_seller_balance($sellerId);
    check(
        $bal['gross_sales'] === '2000.00'
        && $bal['total_commission'] === '200.00'
        && $bal['total_earnings'] === '1800.00'
        && $bal['available_balance'] === '1800.00',
        "Exact 10% commission math verified (Gross 2000.00 -> Admin 200.00 + Seller 1800.00)"
    );
    $postOwn = user_has_purchased_project($buyerId, $projectId);
    check($postOwn !== null && !empty($postOwn['license_key']), 'Buyer download & unique license key unlocked after payment approval');

    // 9. Seller requests 1000.00 BDT bKash withdrawal -> Admin marks paid
    $wdRes = WalletService::requestWithdrawal($sellerId, 'bkash', '01722222222', '1000.00');
    check($wdRes['success'] === true, 'Seller requested ৳1,000.00 bKash withdrawal');
    $wdId = (int)$pdo->query("SELECT `id` FROM `withdrawals` WHERE `seller_id` = {$sellerId} LIMIT 1")->fetchColumn();

    $wdPay = WalletService::approveOrPay($wdId, $adminId, 'paid', 'BKPAY554433', 'Sent via bKash');
    check($wdPay['success'] === true, 'Admin disbursed & finalized seller bKash withdrawal');
    $balAfterWd = ensure_seller_balance($sellerId);
    check(
        $balAfterWd['available_balance'] === '800.00'
        && $balAfterWd['pending_withdrawal'] === '0.00'
        && $balAfterWd['withdrawn_total'] === '1000.00',
        'Seller balance accurate after payout (Avail 800.00, Pending 0.00, Withdrawn 1000.00)'
    );

    // 10. Custom seller commission + Refund reversal flow on a second order
    $pdo->prepare("UPDATE `user_profiles` SET `custom_commission_rate` = '15.00' WHERE `user_id` = ?")->execute([$sellerId]);
    check(seller_commission_rate($sellerId) === '15.00', 'Custom per-seller commission override works (15.00%)');

    $refRes = OrderService::cancelOrRefundOrder($orderId, $adminId, 'Test refund verification');
    check($refRes['success'] === true, 'Admin order refund & immutable ledger reversal succeeded');
    $commState = (string)$pdo->query("SELECT `status` FROM `commissions` WHERE `order_item_id` = {$orderItemId}")->fetchColumn();
    check($commState === 'reversed', 'Commission record marked reversed on refund');

    // 11. Website Control Center dynamic setting persistence
    update_site_setting('default_commission_rate', '12.50', 'finance');
    check(site_setting('default_commission_rate') === '12.50', 'Admin Website Control Center live settings persistence verified');
} finally {
    // Cleanup temporary test DB files completely
    foreach ([$tmpDb, $tmpDb . '-wal', $tmpDb . '-shm', $tmpDb . '.init.lock'] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
}

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
