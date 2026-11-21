<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$items = OrderRepository::buyerPurchasedItems((int)$user['id']);

$pageTitle = 'My Purchases';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css', 'project-details.css'];
$pageScripts = ['dashboard.js'];

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
                        <h1 class="pm-h2">My Purchases</h1>
                        <p class="pm-muted">Every purchased item, license certificate, payment status, and protected ZIP download in one place.</p>
                    </div>
                </div>

                <?php if (empty($items)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('package', 26) ?></div>
                        <h2 class="pm-h3">No purchases yet</h2>
                        <p class="pm-muted" style="max-width:420px;">Purchased digital projects and claimed free starter kits will appear here automatically.</p>
                        <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary">Explore Marketplace</a>
                    </div>
                <?php else: ?>
                    <div class="pm-table-wrap">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th style="text-align:right;">License &amp; Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it):
                                    $canDownload = $it['order_status'] === 'paid' && (int)$it['download_permitted'] === 1 && $it['status'] === 'active'; ?>
                                    <tr>
                                        <td>
                                            <a href="<?= e(url('project.php?slug=' . urlencode((string)$it['project_slug']))) ?>" style="font-weight:700;color:var(--text-primary);display:block;">
                                                <?= e($it['project_title']) ?>
                                            </a>
                                            <small class="pm-muted"><?= e(license_label((string)$it['license_type'])) ?> · v<?= e($it['current_version']) ?></small>
                                        </td>
                                        <td>
                                            <a class="pm-link" href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$it['order_number']))) ?>">
                                                <?= e($it['order_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= e(format_date((string)($it['paid_at'] ?: $it['order_date']))) ?></td>
                                        <td class="pm-tabular" style="font-weight:700;color:var(--text-primary);"><?= e(format_price((string)$it['final_price'], true)) ?></td>
                                        <td><?= render_status_badge((string)$it['order_status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="pm-btn-row" style="justify-content:flex-end;">
                                                <?php if ($canDownload && !empty($it['license_key'])): ?>
                                                    <button type="button"
                                                            class="pm-btn pm-btn-secondary pm-btn-sm"
                                                            data-license-modal
                                                            data-project-title="<?= e($it['project_title']) ?>"
                                                            data-license-key="<?= e($it['license_key']) ?>"
                                                            data-license-type="<?= e(license_label((string)$it['license_type'])) ?>"
                                                            data-order-number="<?= e($it['order_number']) ?>"
                                                            data-purchase-date="<?= e(format_date((string)($it['paid_at'] ?: $it['order_date']))) ?>">
                                                        <?= icon('key', 14) ?><span>License</span>
                                                    </button>
                                                    <a href="<?= e(url('download.php?item=' . (int)$it['id'])) ?>" class="pm-btn pm-btn-primary pm-btn-sm">
                                                        <?= icon('download', 14) ?><span>Download</span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="pm-muted" style="font-size:0.78rem;display:inline-flex;align-items:center;gap:5px;">
                                                        <?= icon('lock', 13) ?>Unlocks after approval
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
require INCLUDES_PATH . '/components/modal.php';
require INCLUDES_PATH . '/layouts/footer.php';
?>
