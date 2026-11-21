<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$sellerRegOpen = site_setting('seller_registration_enabled', '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $accountType = strtolower(trim((string)($_POST['account_type'] ?? 'buyer')));
    if (!in_array($accountType, ['buyer', 'seller'], true)) {
        $accountType = 'buyer';
    }

    $v = (new Validator($_POST))
        ->required('full_name', 'Full name')->minLength('full_name', 2, 'Full name')->maxLength('full_name', 120, 'Full name')
        ->required('username', 'Username')->username('username')
        ->required('email', 'Email address')->email('email')
        ->phone('phone', 'Phone number')
        ->required('password', 'Password')->passwordStrength('password')
        ->matches('password_confirmation', 'password', 'Password confirmation does not match.');

    if ($accountType === 'seller') {
        $v->required('store_name', 'Store / Studio name')->minLength('store_name', 3, 'Store name')->maxLength('store_name', 120, 'Store name')
          ->required('headline', 'Specialty headline')->minLength('headline', 6, 'Specialty headline')->maxLength('headline', 180, 'Specialty headline')
          ->required('payout_method', 'Payout method')->inList('payout_method', ['bkash', 'nagad', 'rocket'], 'Payout method')
          ->required('payout_number', 'Payout mobile number')->phone('payout_number', 'Payout mobile number');
    }

    if (empty($_POST['agree_terms'])) {
        $v->addError('agree_terms', 'Please accept the Terms & Conditions, Privacy Policy, and License Policy.');
    }

    if ($v->fails()) {
        set_old_input($_POST);
        flash('error', $v->firstError());
        redirect(url('register.php'));
    }

    $res = Auth::register($_POST);
    if ($res['success']) {
        $user = $res['user'];
        $siteName = site_setting('site_name', 'CodeVault');
        if ($accountType === 'seller') {
            if (($res['seller_status'] ?? '') === 'approved') {
                flash('success', "Welcome to {$siteName}! Your Seller Studio is ready — start uploading your first project.");
                redirect(url('seller/dashboard.php'));
            }
            flash('success', "Account created! Your Seller Studio application has been submitted for admin approval.");
            redirect(url('seller/become-seller.php'));
        }
        flash('success', "Welcome to {$siteName}! Your buyer account is ready.");
        redirect(url('buyer/dashboard.php'));
    }

    set_old_input($_POST);
    flash('error', (string)$res['message']);
    redirect(url('register.php'));
}

$requestedRole = strtolower(trim((string)($_GET['role'] ?? '')));
$selectedRole = old('account_type', ($requestedRole === 'seller' && $sellerRegOpen) ? 'seller' : 'buyer');
$selectedPayout = old('payout_method', 'bkash');

$pageTitle = 'Create Account';
$pageDescription = 'Create a Buyer or Seller account on ' . site_setting('site_name', 'CodeVault') . ' to buy verified digital projects or sell your own software with bKash, Nagad & Rocket payouts.';
$pageStyles = ['auth.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-auth-section">
    <div class="pm-container">
        <div class="pm-auth-wrap pm-auth-wrap-wide">
            <div class="pm-card pm-auth-card">
                <div class="pm-auth-head">
                    <div class="pm-icon-orb"><?= icon('user-plus', 24) ?></div>
                    <h1 class="pm-h2">Create Your Account</h1>
                    <p class="pm-muted">Choose how you want to start — Buy projects, or open a Seller Studio to publish &amp; earn.</p>
                </div>

                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <form method="post" action="<?= e(url('register.php')) ?>" class="pm-auth-form" data-submit-lock>
                    <?= csrf_field() ?>

                    <div class="pm-field">
                        <span class="pm-label">Select Account Type</span>
                        <div class="pm-role-grid">
                            <label class="pm-role-option">
                                <input type="radio" name="account_type" value="buyer" <?= $selectedRole !== 'seller' ? 'checked' : '' ?>>
                                <span class="pm-role-card">
                                    <span class="pm-role-check"><?= icon('check', 13) ?></span>
                                    <span class="pm-role-icon"><?= icon('shopping-bag', 21) ?></span>
                                    <strong class="pm-role-title">Buyer Account</strong>
                                    <span class="pm-role-desc">Purchase &amp; download verified projects, track orders, and manage license keys.</span>
                                </span>
                            </label>

                            <?php if ($sellerRegOpen): ?>
                                <label class="pm-role-option">
                                    <input type="radio" name="account_type" value="seller" <?= $selectedRole === 'seller' ? 'checked' : '' ?>>
                                    <span class="pm-role-card">
                                        <span class="pm-role-check"><?= icon('check', 13) ?></span>
                                        <span class="pm-role-icon"><?= icon('store', 21) ?></span>
                                        <strong class="pm-role-title">Seller Account</strong>
                                        <span class="pm-role-desc">Upload &amp; sell projects, receive bKash/Nagad/Rocket payouts, and buy projects too.</span>
                                    </span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" class="pm-input" placeholder="Enter your full name" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= e(old('username')) ?>" class="pm-input" placeholder="your_username" required>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="pm-input" placeholder="you@domain.com" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="phone">Mobile Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" class="pm-input" placeholder="017XXXXXXXX">
                        </div>
                    </div>

                    <?php if ($sellerRegOpen): ?>
                        <div id="pmSellerRegPane" class="pm-seller-reg-fields <?= $selectedRole === 'seller' ? 'is-visible' : '' ?>">
                            <div style="display:flex;align-items:center;gap:8px;color:var(--primary);font-weight:700;font-size:0.86rem;">
                                <?= icon('store', 17) ?>
                                <span>Seller Studio &amp; Payout Details</span>
                            </div>

                            <div class="pm-form-grid-2">
                                <div class="pm-field">
                                    <label class="pm-label" for="store_name">Store / Studio Name</label>
                                    <input type="text" id="store_name" name="store_name" value="<?= e(old('store_name')) ?>" class="pm-input" placeholder="e.g. NextWave Software" data-seller-required>
                                </div>
                                <div class="pm-field">
                                    <label class="pm-label" for="headline">Specialty Headline</label>
                                    <input type="text" id="headline" name="headline" value="<?= e(old('headline')) ?>" class="pm-input" placeholder="e.g. PHP, Android & WordPress Creator" data-seller-required>
                                </div>
                            </div>

                            <div class="pm-field">
                                <span class="pm-label">Preferred Withdrawal Method</span>
                                <div class="pm-pay-select-grid">
                                    <?php foreach ([
                                        'bkash' => 'bKash Personal/Agent',
                                        'nagad' => 'Nagad Personal/Agent',
                                        'rocket' => 'DBBL Rocket Account',
                                    ] as $mCode => $mHint): ?>
                                        <label class="pm-pay-option" data-method="<?= e($mCode) ?>">
                                            <input type="radio" name="payout_method" value="<?= e($mCode) ?>" <?= $selectedPayout === $mCode ? 'checked' : '' ?>>
                                            <span class="pm-pay-option-card">
                                                <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                                <?= payment_brand_svg($mCode, 38, true) ?>
                                                <span class="pm-pay-option-meta"><?= e($mHint) ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="pm-field">
                                <label class="pm-label" for="payout_number">Payout Account Number</label>
                                <input type="tel" id="payout_number" name="payout_number" value="<?= e(old('payout_number')) ?>" class="pm-input" placeholder="01XXXXXXXXX" data-seller-required>
                                <small class="pm-field-hint">Creators keep <?= e(number_format(100 - (float)site_setting('default_commission_rate', '10.00'), 0)) ?>% of every verified sale. You can update this anytime in Seller Studio.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="regPassword">Password</label>
                            <div class="pm-input-wrap">
                                <?= icon('lock', 16) ?>
                                <input type="password" id="regPassword" name="password" class="pm-input" style="padding-right:42px;" minlength="8" data-password-strength placeholder="Min 8 characters" required>
                                <button type="button" class="pm-input-action" data-toggle-password="regPassword" aria-label="Show password">
                                    <span class="icon-show"><?= icon('eye', 16) ?></span>
                                    <span class="icon-hide" style="display:none;"><?= icon('eye-off', 16) ?></span>
                                </button>
                            </div>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="password_confirmation">Confirm Password</label>
                            <div class="pm-input-wrap">
                                <?= icon('check-circle', 16) ?>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="pm-input" minlength="8" placeholder="Repeat password" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="pm-strength-meter"><div id="pmStrengthFill" class="pm-strength-fill"></div></div>
                        <small id="pmStrengthLabel" class="pm-field-hint">Use 8+ characters with letters and numbers</small>
                    </div>

                    <label style="display:flex;align-items:flex-start;gap:9px;font-size:0.83rem;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="agree_terms" value="1" style="margin-top:4px;accent-color:var(--primary);" required>
                        <span>I agree to the <a class="pm-link" href="<?= e(url('terms.php')) ?>">Terms &amp; Conditions</a>, <a class="pm-link" href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>, and <a class="pm-link" href="<?= e(url('license-policy.php')) ?>">License Policy</a>.</span>
                    </label>

                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                        <?= icon('user-check', 18) ?>
                        <span id="pmRegSubmitLabel"><?= $selectedRole === 'seller' ? 'Create Seller Account' : 'Create Buyer Account' ?></span>
                    </button>
                </form>

                <div class="pm-auth-foot">
                    Already have an account?
                    <a href="<?= e(url('login.php')) ?>">Sign in here</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
