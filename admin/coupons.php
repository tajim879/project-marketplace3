<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Discount Coupons';
require INCLUDES_PATH . '/layouts/admin-header.php';

$coupons = db()->query("SELECT * FROM `coupons` ORDER BY `id` DESC")->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
foreach ($coupons as $cp) {
    if ((int)$cp['id'] === $editId) {
        $editing = $cp;
        break;
    }
}
?>

<div class="pm-admin-header">
    <div>
        <h1>Discount Coupons &amp; Promo Codes</h1>
        <p>Create percentage or fixed BDT coupons with minimum cart spend, maximum discount caps, usage quotas, and expiration dates.</p>
    </div>
</div>

<div class="pm-admin-split">
    <div class="pm-card pm-card-pad">
        <?php if (empty($coupons)): ?>
            <div class="pm-empty">
                <div class="pm-icon-orb"><?= icon('tag', 26) ?></div>
                <h2 class="pm-h3">No coupons yet</h2>
                <p class="pm-muted">Create your first promotional code using the form on the right.</p>
            </div>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Usage</th><th>Expires</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($coupons as $cp): ?>
                            <tr>
                                <td><code><?= e($cp['code']) ?></code><small class="pm-muted" style="display:block;"><?= e((string)$cp['title']) ?></small></td>
                                <td class="pm-tabular" style="font-weight:700;">
                                    <?= $cp['discount_type'] === 'percentage' ? e($cp['discount_value']) . '%' : e(format_price((string)$cp['discount_value'])) ?>
                                </td>
                                <td class="pm-tabular"><?= e(format_price((string)$cp['min_order_amount'])) ?></td>
                                <td class="pm-tabular"><?= (int)$cp['used_count'] ?> / <?= $cp['usage_limit'] !== null ? (int)$cp['usage_limit'] : '∞' ?></td>
                                <td><?= e(format_date((string)$cp['expires_at'])) ?></td>
                                <td><?= render_status_badge((string)$cp['status']) ?></td>
                                <td style="text-align:right;">
                                    <div class="pm-btn-row" style="justify-content:flex-end;">
                                        <a href="<?= e(url('admin/coupons.php?edit=' . (int)$cp['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a>
                                        <form method="post" action="<?= e(url('actions/admin/coupons.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int)$cp['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm"><?= $cp['status'] === 'active' ? 'Pause' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(url('actions/admin/coupons.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$cp['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" onclick="return confirm('Delete coupon?');"><?= icon('trash', 14) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(url('actions/admin/coupons.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <h2 class="pm-h3"><?= $editing ? 'Edit Coupon ' . e($editing['code']) : 'Create New Coupon' ?></h2>

        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Coupon Code</label><input type="text" name="code" class="pm-input" value="<?= e((string)($editing['code'] ?? '')) ?>" placeholder="LAUNCH20" required></div>
            <div class="pm-field"><label class="pm-label">Campaign Title</label><input type="text" name="title" class="pm-input" value="<?= e((string)($editing['title'] ?? '')) ?>" placeholder="20% Launch Offer" required></div>
        </div>
        <div class="pm-form-grid-2">
            <div class="pm-field">
                <label class="pm-label">Discount Type</label>
                <select name="discount_type" class="pm-select">
                    <option value="percentage" <?= ($editing['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                    <option value="fixed" <?= ($editing['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (৳ BDT)</option>
                </select>
            </div>
            <div class="pm-field"><label class="pm-label">Discount Value</label><input type="number" step="0.01" name="discount_value" class="pm-input pm-tabular" value="<?= e((string)($editing['discount_value'] ?? '')) ?>" required></div>
        </div>
        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Min Cart Amount (৳)</label><input type="number" step="0.01" name="min_order_amount" class="pm-input pm-tabular" value="<?= e((string)($editing['min_order_amount'] ?? '0')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Max Discount Cap (৳ Optional)</label><input type="number" step="0.01" name="max_discount_amount" class="pm-input pm-tabular" value="<?= e((string)($editing['max_discount_amount'] ?? '')) ?>"></div>
        </div>
        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Usage Limit (blank = unlimited)</label><input type="number" min="1" name="usage_limit" class="pm-input pm-tabular" value="<?= e((string)($editing['usage_limit'] ?? '')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Expires On</label><input type="date" name="expires_at" class="pm-input" value="<?= e(!empty($editing['expires_at']) ? substr((string)$editing['expires_at'], 0, 10) : date('Y-m-d', strtotime('+90 days'))) ?>" required></div>
        </div>
        <div class="pm-btn-row">
            <button type="submit" class="pm-btn pm-btn-primary"><?= $editing ? 'Update Coupon' : 'Create Coupon' ?></button>
            <?php if ($editing): ?><a href="<?= e(url('admin/coupons.php')) ?>" class="pm-btn pm-btn-ghost">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
