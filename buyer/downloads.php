<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

$allPurchased = OrderRepository::buyerPurchasedItems($uid);
$authorized = array_values(array_filter(
    $allPurchased,
    static fn(array $i): bool => $i['order_status'] === 'paid' && (int)$i['download_permitted'] === 1 && $i['status'] === 'active'
));
$history = OrderRepository::buyerDownloadHistory($uid, 30);

$pageTitle = 'Secure Downloads Vault';
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
                        <h1 class="pm-h2">Secure Downloads Vault</h1>
                        <p class="pm-muted">Protected private archives unlocked exclusively for your verified paid and community licenses.</p>
                    </div>
                </div>

                <?php if (empty($authorized)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('download', 26) ?></div>
                        <h2 class="pm-h3">No unlocked downloads yet</h2>
                        <p class="pm-muted" style="max-width:440px;">Downloads unlock automatically once an order is approved as Paid, or immediately when claiming a Free Project.</p>
                        <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">Claim a Free Starter Project</a>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:14px;">
                        <?php foreach ($authorized as $item): ?>
                            <article class="pm-card pm-card-pad" style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;">
                                <div style="display:grid;gap:4px;">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <span class="pm-badge pm-badge-primary">v<?= e($item['current_version']) ?></span>
                                        <span class="pm-badge pm-badge-muted"><?= e($item['file_size_label']) ?></span>
                                        <span class="pm-muted" style="font-size:0.78rem;">Updated <?= e(format_date((string)$item['last_updated_at'])) ?></span>
                                    </div>
                                    <h3 class="pm-h3" style="margin-top:2px;">
                                        <a href="<?= e(url('project.php?slug=' . urlencode((string)$item['project_slug']))) ?>"><?= e($item['project_title']) ?></a>
                                    </h3>
                                    <small class="pm-muted">
                                        Order <?= e($item['order_number']) ?> · Downloaded <?= (int)$item['download_count'] ?><?= $item['download_limit'] ? ' / ' . (int)$item['download_limit'] : '' ?> times
                                    </small>
                                </div>

                                <div class="pm-btn-row">
                                    <?php if (!empty($item['license_key'])): ?>
                                        <button type="button"
                                                class="pm-btn pm-btn-secondary pm-btn-sm"
                                                data-license-modal
                                                data-project-title="<?= e($item['project_title']) ?>"
                                                data-license-key="<?= e($item['license_key']) ?>"
                                                data-license-type="<?= e(license_label((string)$item['license_type'])) ?>"
                                                data-order-number="<?= e($item['order_number']) ?>"
                                                data-purchase-date="<?= e(format_date((string)$item['paid_at'])) ?>">
                                            <?= icon('key', 15) ?><span>License Key</span>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= e(url('download.php?item=' . (int)$item['id'])) ?>" class="pm-btn pm-btn-primary">
                                        <?= icon('download', 16) ?><span>Secure ZIP Download</span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Download History Log -->
                <div>
                    <h2 class="pm-h3" style="margin-bottom:14px;">Download Audit History</h2>
                    <?php if (empty($history)): ?>
                        <div class="pm-card pm-card-pad"><p class="pm-muted">No file download events recorded yet.</p></div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Version</th>
                                        <th>Order</th>
                                        <th>IP Address</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td style="font-weight:600;color:var(--text-primary);"><?= e($h['project_title']) ?></td>
                                            <td><span class="pm-badge pm-badge-muted">v<?= e($h['version']) ?></span></td>
                                            <td><?= e($h['order_number'] ?: '—') ?></td>
                                            <td><code><?= e($h['ip_address'] ?: '—') ?></code></td>
                                            <td><?= e(format_date((string)$h['downloaded_at'], 'M j, Y · g:i A')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require INCLUDES_PATH . '/components/modal.php';
require INCLUDES_PATH . '/layouts/footer.php';
?>
