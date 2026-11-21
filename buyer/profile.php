<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();

$pageTitle = 'Profile Settings';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dashboard-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-dashboard-shell">
            <?php require INCLUDES_PATH . '/layouts/buyer-sidebar.php'; ?>

            <div class="pm-dash-main">
                <div class="pm-dash-mobile-bar">
                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-drawer-open="pmBuyerSidebar">
                        <?= icon('menu', 16) ?><span>Dashboard Menu</span>
                    </button>
                </div>

                <div class="pm-dash-header">
                    <div>
                        <h1 class="pm-h2">Profile Settings</h1>
                        <p class="pm-muted">Manage your personal details, avatar photo, and invoice billing location.</p>
                    </div>
                </div>

                <form method="post" action="<?= e(url('actions/buyer/profile.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:20px;padding:30px;" data-submit-lock>
                    <?= csrf_field() ?>

                    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding-bottom:18px;border-bottom:1px solid var(--border);">
                        <span class="pm-avatar pm-avatar-xl">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= e(media_url((string)$user['avatar'])) ?>" alt="<?= e($user['full_name']) ?>">
                            <?php else: ?>
                                <?= e(user_initials((string)$user['full_name'])) ?>
                            <?php endif; ?>
                        </span>
                        <div style="display:grid;gap:6px;">
                            <label class="pm-label" for="avatarInput">Profile Avatar</label>
                            <input type="file" id="avatarInput" name="avatar" accept=".jpg,.jpeg,.png,.webp" style="font-size:0.83rem;">
                            <small class="pm-field-hint">JPG, PNG, or WebP · Max <?= e(site_setting('max_image_size_mb', '5')) ?> MB · MIME &amp; dimension validated</small>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="pName">Full Name</label>
                            <input id="pName" name="full_name" class="pm-input" value="<?= e($user['full_name']) ?>" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="pPhone">Phone Number</label>
                            <input id="pPhone" name="phone" class="pm-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="017XX-XXXXXX">
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label">Username (Immutable)</label>
                            <input class="pm-input" value="@<?= e($user['username']) ?>" disabled>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label">Registered Email</label>
                            <input class="pm-input" value="<?= e($user['email']) ?>" disabled>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="pHead">Professional Headline</label>
                        <input id="pHead" name="headline" class="pm-input" value="<?= e($user['headline'] ?? '') ?>" placeholder="e.g. Software Engineer & Product Founder">
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="pBio">Bio</label>
                        <textarea id="pBio" name="bio" class="pm-textarea" placeholder="Tell creators and buyers a little about your work..."><?= e($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="pCity">City</label>
                            <input id="pCity" name="city" class="pm-input" value="<?= e($user['city'] ?? '') ?>" placeholder="Dhaka / Chattogram">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="pCountry">Country</label>
                            <input id="pCountry" name="country" class="pm-input" value="<?= e($user['country'] ?? 'Bangladesh') ?>">
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="pAddr">Invoice Address</label>
                        <input id="pAddr" name="address" class="pm-input" value="<?= e($user['address'] ?? '') ?>" placeholder="Street address for receipts">
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="pWeb">Website URL</label>
                            <input type="url" id="pWeb" name="website_url" class="pm-input" value="<?= e($user['website_url'] ?? '') ?>" placeholder="https://example.com">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="pGit">GitHub URL</label>
                            <input type="url" id="pGit" name="github_url" class="pm-input" value="<?= e($user['github_url'] ?? '') ?>" placeholder="https://github.com/username">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="pm-btn pm-btn-primary">
                            <?= icon('check', 16) ?><span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
