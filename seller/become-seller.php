<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$viewer = current_user();
$commission = (float)site_setting('default_commission_rate', '10.00');
$authorKeep = max(0, 100 - $commission);
$minWd = site_setting('min_withdrawal_amount', '500.00');
$selPayout = old('payout_method', (string)($viewer['payout_method'] ?? 'bkash'));

$pageTitle = 'Become a Verified Seller';
$pageDescription = 'Publish your PHP projects, Android & Kotlin apps, WordPress solutions, Python tools, and UI kits and earn ' . $authorKeep . '% on every verified sale.';
$pageStyles = ['homepage.css', 'auth.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Become a Seller</span>
        </nav>
        <div class="pm-page-header-row">
            <div style="max-width:700px;">
                <span class="pm-eyebrow" style="margin-bottom:10px;"><?= icon('store', 14) ?><span><?= number_format($authorKeep, 0) ?>% Creator Revenue Share</span></span>
                <h1 class="pm-h1">Sell Your Digital Projects on <?= e(site_setting('site_name', 'CodeVault')) ?></h1>
                <p class="pm-muted" style="margin-top:8px;">Use one account to both buy and sell. Upload inspected source code, keep <?= number_format($authorKeep, 0) ?>% of every verified sale, and withdraw directly to your bKash, Nagad, or Rocket wallet.</p>
                <div class="pm-pay-strip" style="margin-top:16px;">
                    <?= payment_brand_badge('bkash', 'lg') ?>
                    <?= payment_brand_badge('nagad', 'lg') ?>
                    <?= payment_brand_badge('rocket', 'lg') ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-grid-3" style="margin-bottom:40px;">
            <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                <span class="pm-stat-icon is-success"><?= icon('wallet', 22) ?></span>
                <h3 class="pm-h3">Keep <?= number_format($authorKeep, 0) ?>% Per Sale</h3>
                <p class="pm-muted">Transparent <?= number_format($commission, 0) ?>% platform fee calculated in an immutable financial ledger. Minimum withdrawal <?= e(format_price($minWd)) ?>.</p>
            </div>
            <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                <span class="pm-stat-icon"><?= icon('shield', 22) ?></span>
                <h3 class="pm-h3">Protected Source Vault</h3>
                <p class="pm-muted">Your ZIP archives are stored privately and streamed exclusively to verified paid license holders.</p>
            </div>
            <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                <span class="pm-stat-icon is-warning"><?= icon('banknote', 22) ?></span>
                <h3 class="pm-h3">bKash, Nagad &amp; Rocket Payouts</h3>
                <p class="pm-muted">Request fast local BDT withdrawals directly to your mobile wallet with full audit &amp; reference tracking.</p>
            </div>
        </div>

        <div style="max-width:740px;margin:0 auto;">
            <?php if (!$viewer): ?>
                <div class="pm-card pm-empty">
                    <div class="pm-icon-orb"><?= icon('store', 26) ?></div>
                    <h2 class="pm-h2">Open Your Seller Studio</h2>
                    <p class="pm-muted" style="max-width:480px;">Select "Seller Account" during registration to set up your storefront and payout number in under a minute, or sign in with an existing buyer account.</p>
                    <div class="pm-btn-row" style="margin-top:10px;">
                        <a href="<?= e(url('register.php?role=seller')) ?>" class="pm-btn pm-btn-primary pm-btn-lg"><?= icon('store', 17) ?><span>Register as Seller</span></a>
                        <a href="<?= e(url('login.php')) ?>" class="pm-btn pm-btn-secondary pm-btn-lg"><?= icon('lock', 17) ?><span>Sign In</span></a>
                    </div>
                </div>
            <?php elseif (is_approved_seller($viewer)): ?>
                <div class="pm-card pm-empty">
                    <div class="pm-icon-orb is-success"><?= icon('badge-check', 28) ?></div>
                    <h2 class="pm-h2">Your Seller Studio Is Active</h2>
                    <p class="pm-muted" style="max-width:500px;">Upload new digital projects, publish version updates, track verified BDT earnings, and withdraw to bKash, Nagad, or Rocket.</p>
                    <div class="pm-btn-row" style="margin-top:10px;">
                        <a href="<?= e(url('seller/dashboard.php')) ?>" class="pm-btn pm-btn-primary pm-btn-lg">
                            <?= icon('grid', 17) ?><span>Open Seller Studio</span>
                        </a>
                        <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-secondary pm-btn-lg">
                            <?= icon('upload', 17) ?><span>Upload Project</span>
                        </a>
                        <a href="<?= e(url('seller-profile.php?username=' . urlencode((string)$viewer['username']))) ?>" class="pm-btn pm-btn-ghost">
                            <?= icon('external-link', 16) ?><span>Public Storefront</span>
                        </a>
                    </div>
                </div>
            <?php elseif (($viewer['seller_status'] ?? '') === 'pending'): ?>
                <div class="pm-card pm-empty">
                    <div class="pm-icon-orb"><?= icon('clock', 28) ?></div>
                    <h2 class="pm-h2">Application Under Review</h2>
                    <p class="pm-muted" style="max-width:500px;">
                        Your application for <strong><?= e($viewer['store_name'] ?: $viewer['full_name']) ?></strong> was submitted on <?= e(format_date((string)$viewer['seller_applied_at'], 'M j, Y · g:i A')) ?>. You will receive an in-app notification as soon as it is approved.
                    </p>
                    <a href="<?= e(url('buyer/dashboard.php')) ?>" class="pm-btn pm-btn-secondary" style="margin-top:8px;">
                        <?= icon('arrow-left', 16) ?><span>Return to Buyer Dashboard</span>
                    </a>
                </div>
            <?php else: ?>
                <?php if (($viewer['seller_status'] ?? '') === 'rejected' && !empty($viewer['seller_rejection_reason'])): ?>
                    <div class="pm-alert pm-alert-danger" style="margin-bottom:16px;">
                        <?= icon('alert-circle', 18) ?>
                        <span><strong>Previous review note:</strong> <?= e($viewer['seller_rejection_reason']) ?></span>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('actions/seller/apply.php')) ?>" class="pm-card pm-card-pad" style="padding:34px;display:grid;gap:18px;" data-submit-lock>
                    <?= csrf_field() ?>
                    <div>
                        <h2 class="pm-h2">Activate Seller Studio</h2>
                        <p class="pm-muted" style="margin-top:4px;">Upgrading account <strong><?= e($viewer['full_name']) ?></strong> (@<?= e($viewer['username']) ?>) to buy &amp; sell.</p>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="sStore">Studio / Store Name</label>
                            <input id="sStore" name="store_name" class="pm-input" value="<?= e(old('store_name', (string)($viewer['store_name'] ?? ''))) ?>" placeholder="e.g. BengalCraft Studio" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="sHead">Specialty Headline</label>
                            <input id="sHead" name="headline" class="pm-input" value="<?= e(old('headline', (string)($viewer['headline'] ?? ''))) ?>" placeholder="Full-Stack PHP & Kotlin Engineer" required>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="sSkills">Core Technologies (comma separated)</label>
                            <input id="sSkills" name="skills" class="pm-input" value="<?= e(old('skills', (string)($viewer['skills'] ?? ''))) ?>" placeholder="PHP 8, SQLite, Kotlin, WordPress" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="sPort">GitHub or Portfolio URL (Optional)</label>
                            <input type="url" id="sPort" name="portfolio_url" class="pm-input" value="<?= e(old('portfolio_url', (string)($viewer['portfolio_url'] ?? ''))) ?>" placeholder="https://github.com/username">
                        </div>
                    </div>

                    <div class="pm-field">
                        <span class="pm-label">Preferred Withdrawal Method</span>
                        <div class="pm-pay-select-grid">
                            <?php foreach ([
                                'bkash' => 'bKash Payout',
                                'nagad' => 'Nagad Payout',
                                'rocket' => 'DBBL Rocket',
                            ] as $mCode => $mHint): ?>
                                <label class="pm-pay-option" data-method="<?= e($mCode) ?>">
                                    <input type="radio" name="payout_method" value="<?= e($mCode) ?>" <?= $selPayout === $mCode ? 'checked' : '' ?> required>
                                    <span class="pm-pay-option-card">
                                        <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                        <?= payment_brand_svg($mCode, 40, true) ?>
                                        <span class="pm-pay-option-meta"><?= e($mHint) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="sNum">bKash / Nagad / Rocket Account Number</label>
                        <input id="sNum" name="payout_number" class="pm-input" value="<?= e(old('payout_number', (string)($viewer['payout_number'] ?? $viewer['phone'] ?? ''))) ?>" placeholder="01XXXXXXXXX" required>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="sNote">What digital projects will you publish?</label>
                        <textarea id="sNote" name="application_note" class="pm-textarea" placeholder="Describe your software projects, documentation standards, and buyer support..." required><?= e(old('application_note')) ?></textarea>
                    </div>

                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg">
                        <?= icon('badge-check', 18) ?><span><?= site_setting('seller_auto_approve', '1') === '1' ? 'Activate Seller Studio Now' : 'Submit Seller Application' ?></span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
