<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $v = (new Validator($_POST))->required('email', 'Email address')->email('email');
    if ($v->fails()) {
        flash('error', $v->firstError());
    } else {
        Auth::createPasswordReset((string)$_POST['email']);
        // Uniform privacy-safe confirmation prevents account enumeration
        flash('success', 'If an active account matches that email, a secure 60-minute password reset link has been dispatched.');
    }
    redirect(url('forgot-password.php'));
}

$pageTitle = 'Forgot Password';
$pageStyles = ['auth.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-auth-section">
    <div class="pm-container">
        <div class="pm-auth-wrap">
            <div class="pm-card pm-auth-card">
                <div class="pm-auth-head">
                    <div class="pm-icon-orb"><?= icon('key', 24) ?></div>
                    <h1 class="pm-h2">Reset Your Password</h1>
                    <p class="pm-muted">Enter your account email and we will issue a one-time cryptographic reset link.</p>
                </div>

                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <form method="post" action="<?= e(url('forgot-password.php')) ?>" class="pm-auth-form" data-submit-lock>
                    <?= csrf_field() ?>
                    <div class="pm-field">
                        <label class="pm-label" for="fpEmail">Registered Email Address</label>
                        <div class="pm-input-wrap">
                            <?= icon('mail', 17) ?>
                            <input type="email" id="fpEmail" name="email" class="pm-input" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                        <?= icon('send', 16) ?><span>Send Reset Link</span>
                    </button>
                </form>

                <div class="pm-auth-foot">
                    Remembered your password? <a href="<?= e(url('login.php')) ?>">Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
