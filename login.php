<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        set_old_input($_POST);
        flash('error', 'Please enter both your email/username and password.');
    } else {
        $res = Auth::attemptLogin($identifier, $password);
        if ($res['success']) {
            $user = $res['user'];
            $intended = (string)($_SESSION['intended_url'] ?? '');
            unset($_SESSION['intended_url']);
            if ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//') && !str_contains($intended, 'admin')) {
                redirect($intended);
            }
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            if ((int)($user['is_seller'] ?? 0) === 1 && ($user['seller_status'] ?? '') === 'approved') {
                redirect(url('seller/dashboard.php'));
            }
            redirect(url('buyer/dashboard.php'));
        }
        set_old_input($_POST);
        flash('error', (string)$res['message']);
    }
    redirect(url('login.php'));
}

$pageTitle = 'Sign In';
$pageDescription = 'Sign in to access your Buyer Dashboard, Seller Studio, verified purchases, and protected downloads.';
$pageStyles = ['auth.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-auth-section">
    <div class="pm-container">
        <div class="pm-auth-wrap">
            <div class="pm-card pm-auth-card">
                <div class="pm-auth-head">
                    <div class="pm-icon-orb"><?= icon('lock', 24) ?></div>
                    <h1 class="pm-h2">Welcome Back</h1>
                    <p class="pm-muted">Sign in to manage your purchases, downloads &amp; Seller Studio.</p>
                </div>

                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <form method="post" action="<?= e(url('login.php')) ?>" class="pm-auth-form" data-submit-lock>
                    <?= csrf_field() ?>

                    <div class="pm-field">
                        <label class="pm-label" for="identifier">Email or Username</label>
                        <div class="pm-input-wrap">
                            <?= icon('user', 17) ?>
                            <input type="text" id="identifier" name="identifier" value="<?= e(old('identifier')) ?>" class="pm-input" placeholder="you@domain.com or username" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="password">
                            <span>Password</span>
                            <a href="<?= e(url('forgot-password.php')) ?>" class="pm-link" style="font-size:0.79rem;">Forgot password?</a>
                        </label>
                        <div class="pm-input-wrap">
                            <?= icon('lock', 17) ?>
                            <input type="password" id="password" name="password" class="pm-input" style="padding-right:44px;" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="pm-input-action" data-toggle-password="password" aria-label="Show password">
                                <span class="icon-show"><?= icon('eye', 16) ?></span>
                                <span class="icon-hide" style="display:none;"><?= icon('eye-off', 16) ?></span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                        <span>Sign In</span><?= icon('arrow-right', 17) ?>
                    </button>
                </form>

                <div class="pm-auth-foot">
                    Don't have an account yet?
                    <a href="<?= e(url('register.php')) ?>">Create Buyer or Seller account</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
