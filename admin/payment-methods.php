<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Payment Methods (bKash, Nagad, Rocket)';
require INCLUDES_PATH . '/layouts/admin-header.php';

$methods = all_payment_methods();
?>

<div class="pm-admin-header">
    <div>
        <h1>Manual Payment Gateways — bKash, Nagad &amp; Rocket</h1>
        <p>Set your real receiving mobile wallet numbers, account types (Personal / Agent / Merchant), step-by-step checkout instructions, and active status.</p>
    </div>
</div>

<div class="pm-grid-3">
    <?php foreach ($methods as $m): ?>
        <form method="post" action="<?= e(url('actions/admin/payment-methods.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:10px;border-bottom:1px solid var(--border);">
                <?= payment_brand_badge((string)$m['code'], 'lg') ?>
                <?= (int)$m['is_enabled'] === 1 ? '<span class="pm-badge pm-badge-success">Active</span>' : '<span class="pm-badge pm-badge-danger">Disabled</span>' ?>
            </div>

            <div class="pm-field">
                <label class="pm-label">Display Name</label>
                <input type="text" name="name" class="pm-input" value="<?= e($m['name']) ?>" required>
            </div>

            <div class="pm-field">
                <label class="pm-label">Receiving Wallet Number</label>
                <input type="text" name="account_number" class="pm-input pm-tabular" value="<?= e($m['account_number']) ?>" placeholder="01XXXXXXXXX" required>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-field">
                    <label class="pm-label">Account Type</label>
                    <select name="account_type" class="pm-select">
                        <?php foreach (['personal' => 'Personal', 'merchant' => 'Merchant', 'agent' => 'Agent'] as $tk => $tl): ?>
                            <option value="<?= $tk ?>" <?= $m['account_type'] === $tk ? 'selected' : '' ?>><?= $tl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pm-field">
                    <label class="pm-label">Display Order</label>
                    <input type="number" name="sort_order" class="pm-input pm-tabular" value="<?= (int)$m['sort_order'] ?>">
                </div>
            </div>

            <div class="pm-field">
                <label class="pm-label">Buyer Checkout Instructions</label>
                <textarea name="instructions" class="pm-textarea" rows="5" required><?= e($m['instructions']) ?></textarea>
            </div>

            <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;font-weight:600;">
                <input type="checkbox" name="is_enabled" value="1" <?= (int)$m['is_enabled'] === 1 ? 'checked' : '' ?>>
                <span>Enable <?= e($m['name']) ?> at Checkout</span>
            </label>

            <button type="submit" class="pm-btn pm-btn-primary pm-btn-block"><?= icon('check', 16) ?><span>Save <?= e($m['name']) ?> Settings</span></button>
        </form>
    <?php endforeach; ?>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
