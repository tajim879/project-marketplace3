<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$selectedWallet = (string)($user['payout_method'] ?: 'bkash');

$pageTitle = 'Store Profile & Payout Settings — Seller Studio';
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

                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h1 class="pm-h2">Store Profile &amp; Payout Wallet</h1>
                        <p class="pm-muted">Customize your public storefront and default bKash/Nagad/Rocket payout destination.</p>
                    </div>
                    <a href="<?= e(url('seller-profile.php?username=' . urlencode((string)$user['username']))) ?>" class="pm-btn pm-btn-secondary">
                        <?= icon('external-link', 16) ?><span>View Storefront</span>
                    </a>
                </div>

                <form method="post" action="<?= e(url('actions/seller/store-profile.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:18px;" data-submit-lock>
                    <?= csrf_field() ?>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="store_name">Store / Brand Name</label>
                            <input type="text" id="store_name" name="store_name" class="pm-input" value="<?= e((string)($user['store_name'] ?? '')) ?>" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="avatar">Store / Author Avatar (JPG/PNG/WebP)</label>
                            <input type="file" id="avatar" name="avatar" class="pm-input" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="headline">Professional Headline</label>
                        <input type="text" id="headline" name="headline" class="pm-input" value="<?= e((string)($user['headline'] ?? '')) ?>" required>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="bio">Store Bio</label>
                        <textarea id="bio" name="bio" class="pm-textarea" rows="4"><?= e((string)($user['bio'] ?? '')) ?></textarea>
                    </div>

                    <div class="pm-grid-3">
                        <div class="pm-field">
                            <label class="pm-label" for="skills">Primary Skills / Stack</label>
                            <input type="text" id="skills" name="skills" class="pm-input" value="<?= e((string)($user['skills'] ?? '')) ?>" placeholder="PHP, Laravel, React, Flutter">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="support_email">Buyer Support Email</label>
                            <input type="email" id="support_email" name="support_email" class="pm-input" value="<?= e((string)($user['support_email'] ?: $user['email'])) ?>">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="response_time">Response Time</label>
                            <input type="text" id="response_time" name="response_time" class="pm-input" value="<?= e((string)($user['response_time'] ?? 'Usually replies within 2 hours')) ?>">
                        </div>
                    </div>

                    <div class="pm-grid-3">
                        <div class="pm-field"><label class="pm-label" for="website_url">Website URL</label><input type="url" id="website_url" name="website_url" class="pm-input" value="<?= e((string)($user['website_url'] ?? '')) ?>"></div>
                        <div class="pm-field"><label class="pm-label" for="github_url">GitHub URL</label><input type="url" id="github_url" name="github_url" class="pm-input" value="<?= e((string)($user['github_url'] ?? '')) ?>"></div>
                        <div class="pm-field"><label class="pm-label" for="portfolio_url">Portfolio URL</label><input type="url" id="portfolio_url" name="portfolio_url" class="pm-input" value="<?= e((string)($user['portfolio_url'] ?? '')) ?>"></div>
                    </div>

                    <div style="padding-top:14px;border-top:1px solid var(--border);display:grid;gap:14px;">
                        <h2 class="pm-h3">Default Payout Wallet (BDT)</h2>
                        <div class="pm-pay-select-grid">
                            <?php foreach (['bkash' => 'bKash Mobile Wallet', 'nagad' => 'Nagad Mobile Wallet', 'rocket' => 'Rocket DBBL Mobile'] as $wCode => $wSub): ?>
                                <label class="pm-pay-option" data-method="<?= e($wCode) ?>">
                                    <input type="radio" name="payout_method" value="<?= e($wCode) ?>" <?= $selectedWallet === $wCode ? 'checked' : '' ?> required>
                                    <span class="pm-pay-option-card">
                                        <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                        <span class="pm-pay-option-logo"><?= payment_brand_svg($wCode, 50) ?></span>
                                        <span class="pm-pay-option-meta"><?= e($wSub) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="payout_number">Payout Mobile Number</label>
                                <input type="tel" id="payout_number" name="payout_number" class="pm-input pm-tabular" value="<?= e((string)($user['payout_number'] ?? '')) ?>" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="payout_account_type">Account Type</label>
                                <select id="payout_account_type" name="payout_account_type" class="pm-select">
                                    <?php foreach (['personal' => 'Personal', 'agent' => 'Agent', 'merchant' => 'Merchant'] as $ak => $al): ?>
                                        <option value="<?= $ak ?>" <?= ($user['payout_account_type'] ?? 'personal') === $ak ? 'selected' : '' ?>><?= e($al) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 17) ?><span>Save Store &amp; Payout Settings</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
