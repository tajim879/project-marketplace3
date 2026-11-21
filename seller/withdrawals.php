<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];
$bal = ensure_seller_balance($uid);
$minW = site_setting('min_withdrawal_amount', '500.00');
$maxW = site_setting('max_withdrawal_amount', '100000.00');
$selectedWallet = old('method', (string)($user['payout_method'] ?: 'bkash'));
$defaultNum = old('account_number', (string)($user['payout_number'] ?: ($user['phone'] ?? '')));

$wStmt = db()->prepare("SELECT * FROM `withdrawals` WHERE `seller_id` = ? ORDER BY `id` DESC");
$wStmt->execute([$uid]);
$withdrawals = $wStmt->fetchAll();

$pageTitle = 'Withdrawals — Seller Studio';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['dashboard.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dash-shell">
    <div class="pm-container">
        <div class="pm-dash-grid">
            <?php require INCLUDES_PATH . '/layouts/seller-sidebar.php'; ?>

            <div class="pm-dash-main">
                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <div>
                    <h1 class="pm-h2">Payouts &amp; Withdrawals</h1>
                    <p class="pm-muted">Withdraw your available BDT earnings directly to your bKash, Nagad, or Rocket mobile wallet.</p>
                </div>

                <div class="pm-grid-3">
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-success"><?= icon('wallet', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['available_balance'])) ?></strong><span class="pm-kpi-label">Available Balance</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-warning"><?= icon('clock', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['pending_withdrawal'])) ?></strong><span class="pm-kpi-label">Pending Review</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon"><?= icon('check-circle', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['withdrawn_total'])) ?></strong><span class="pm-kpi-label">Paid Out Lifetime</span></div></div>
                </div>

                <form method="post" action="<?= e(url('actions/seller/withdraw.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:18px;" data-submit-lock>
                    <?= csrf_field() ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                        <h2 class="pm-h3">Request New Withdrawal</h2>
                        <span class="pm-muted" style="font-size:0.82rem;">Minimum: <strong><?= e(format_price($minW)) ?></strong> · Max: <?= e(format_price($maxW)) ?></span>
                    </div>

                    <div class="pm-field">
                        <span class="pm-label">1. Select Receiving Mobile Wallet</span>
                        <div class="pm-pay-select-grid">
                            <?php foreach (['bkash' => 'bKash Personal / Agent', 'nagad' => 'Nagad Personal / Agent', 'rocket' => 'Rocket DBBL Mobile'] as $wCode => $wSub): ?>
                                <label class="pm-pay-option" data-method="<?= e($wCode) ?>">
                                    <input type="radio" name="method" value="<?= e($wCode) ?>" <?= $selectedWallet === $wCode ? 'checked' : '' ?> required>
                                    <span class="pm-pay-option-card">
                                        <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                        <span class="pm-pay-option-logo"><?= payment_brand_svg($wCode, 50) ?></span>
                                        <span class="pm-pay-option-meta"><?= e($wSub) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="account_number">2. Receiving Wallet Number</label>
                            <input type="tel" id="account_number" name="account_number" class="pm-input pm-tabular" value="<?= e($defaultNum) ?>" placeholder="01XXXXXXXXX" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="amount">3. Withdrawal Amount (৳ BDT)</label>
                            <input type="number" step="0.01" min="<?= e($minW) ?>" max="<?= e((string)$bal['available_balance']) ?>" id="amount" name="amount" class="pm-input pm-tabular" value="<?= e(old('amount')) ?>" placeholder="<?= e($minW) ?>" required>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="pm-btn pm-btn-primary" <?= bccomp((string)$bal['available_balance'], $minW, 2) < 0 ? 'disabled' : '' ?>>
                            <?= icon('send', 17) ?><span>Submit Withdrawal Request</span>
                        </button>
                        <?php if (bccomp((string)$bal['available_balance'], $minW, 2) < 0): ?>
                            <small class="pm-muted" style="margin-left:10px;">Reach at least <?= e(format_price($minW)) ?> available balance to withdraw.</small>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <h2 class="pm-h3">Withdrawal History</h2>
                    <?php if (empty($withdrawals)): ?>
                        <p class="pm-muted" style="padding:18px 0;">No withdrawal requests submitted yet.</p>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Wallet</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payout Ref / Note</th>
                                        <th>Requested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($withdrawals as $w): ?>
                                        <tr>
                                            <td><code><?= e($w['request_number']) ?></code></td>
                                            <td><?= payment_brand_badge((string)$w['method'], 'sm') ?></td>
                                            <td class="pm-tabular"><?= e($w['account_number']) ?></td>
                                            <td class="pm-tabular" style="font-weight:700;"><?= e(format_price((string)$w['amount'])) ?></td>
                                            <td><?= render_status_badge((string)$w['status']) ?></td>
                                            <td>
                                                <?php if (!empty($w['payment_reference'])): ?>
                                                    <code><?= e($w['payment_reference']) ?></code>
                                                <?php elseif (!empty($w['rejection_reason'])): ?>
                                                    <span style="color:var(--danger);font-size:0.8rem;"><?= e($w['rejection_reason']) ?></span>
                                                <?php elseif (!empty($w['admin_note'])): ?>
                                                    <span class="pm-muted" style="font-size:0.8rem;"><?= e($w['admin_note']) ?></span>
                                                <?php else: ?>
                                                    <span class="pm-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e(format_datetime((string)$w['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
