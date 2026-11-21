<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$email = trim((string)($_GET['email'] ?? $_POST['email'] ?? ''));
$validRecord = Auth::verifyResetToken($email, $token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $v = (new Validator($_POST))
        ->required('password', 'New password')->passwordStrength('password', 'New password')
        ->matches('password_confirmation', 'password', 'Passwords do not match.');

    if ($v->fails()) {
        flash('error', $v->firstError());
        redirect(url('reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email)));
    }

    if (Auth::completePasswordReset($email, $token, (string)$_POST['password'])) {
        flash('success', 'Your password has been reset. You can now sign in with your new password.');
        redirect(url('login.php'));
    }

    flash('error', 'This reset link is invalid or has expired. Please request a fresh link.');
    redirect(url('forgot-password.php'));
}

$pageTitle = 'Set New Password';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['auth.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-auth-section">
    <div class="pm-container">
        <div class="pm-auth-wrap">
            <div class="pm-card pm-auth-card">
                <div class="pm-auth-head">
                    <div class="pm-icon-orb"><?= icon('shield', 24) ?></div>
                    <h1 class="pm-h2">Choose a New Password</h1>
                </div>

                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <?php if (!$validRecord): ?>
                    <div class="pm-alert pm-alert-warning" style="margin:0;">
                        <?= icon('alert-circle', 18) ?>
                        <span>This password reset token is invalid, already used, or expired.</span>
                    </div>
                    <a href="<?= e(url('forgot-password.php')) ?>" class="pm-btn pm-btn-primary pm-btn-block">
                        <?= icon('refresh', 16) ?><span>Request a New Reset Link</span>
                    </a>
                <?php else: ?>
                    <form method="post" action="<?= e(url('reset-password.php')) ?>" class="pm-auth-form" data-submit-lock>
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= e($token) ?>">
                        <input type="hidden" name="email" value="<?= e($email) ?>">

                        <div class="pm-field">
                            <label class="pm-label" for="newPw">New Password</label>
                            <input type="password" id="newPw" name="password" class="pm-input" minlength="8" data-password-strength required>
                            <div class="pm-strength-meter"><div id="pmStrengthFill" class="pm-strength-fill"></div></div>
                            <small id="pmStrengthLabel" class="pm-field-hint">Use 8+ characters with letters and numbers</small>
                        </div>

                        <div class="pm-field">
                            <label class="pm-label" for="newPw2">Confirm New Password</label>
                            <input type="password" id="newPw2" name="password_confirmation" class="pm-input" minlength="8" required>
                        </div>

                        <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                            <?= icon('check-circle', 18) ?><span>Update Password</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
