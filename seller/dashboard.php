<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];
$pdo = db();
$bal = ensure_seller_balance($uid);
$commRate = seller_commission_rate($uid);

$pCounts = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN `status` = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) AS pending,
        COALESCE(SUM(`sales_count`), 0) AS total_sales,
        COALESCE(SUM(`views_count`), 0) AS total_views
     FROM `projects` WHERE `seller_id` = ?"
);
$pCounts->execute([$uid]);
$pStats = $pCounts->fetch() ?: ['total' => 0, 'approved' => 0, 'pending' => 0, 'total_sales' => 0, 'total_views' => 0];

$recentSales = $pdo->prepare(
    "SELECT oi.*, o.order_number, o.paid_at, u.full_name AS buyer_name
     FROM `order_items` oi
     INNER JOIN `orders` o ON o.id = oi.order_id
     INNER JOIN `users` u ON u.id = o.buyer_id
     WHERE oi.seller_id = ? AND o.status = 'paid' AND oi.status = 'active'
     ORDER BY o.paid_at DESC, oi.id DESC LIMIT 6"
);
$recentSales->execute([$uid]);
$sales = $recentSales->fetchAll();

$recentProjects = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM `projects` p
     INNER JOIN `categories` c ON c.id = p.category_id
     WHERE p.seller_id = ? ORDER BY p.created_at DESC LIMIT 5"
);
$recentProjects->execute([$uid]);
$myProjects = $recentProjects->fetchAll();

$pageTitle = 'Seller Studio';
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

                <div class="pm-dash-banner">
                    <div>
                        <span class="pm-badge pm-badge-primary" style="margin-bottom:8px;">
                            <?= icon('store', 13) ?>
                            <span>Creator Keep: <?= e(number_format(100 - (float)$commRate, 0)) ?>% per Verified Sale</span>
                        </span>
                        <h1 class="pm-h2"><?= e($user['store_name'] ?: $user['full_name']) ?></h1>
                        <p class="pm-muted" style="margin-top:4px;">Upload digital products, manage versions, track verified BDT revenue, and withdraw via bKash, Nagad &amp; Rocket.</p>
                    </div>
                    <div class="pm-btn-row">
                        <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary">
                            <?= icon('upload', 17) ?><span>Upload Project</span>
                        </a>
                        <a href="<?= e(url('seller/withdrawals.php')) ?>" class="pm-btn pm-btn-success">
                            <?= icon('wallet', 17) ?><span>Withdraw</span>
                        </a>
                    </div>
                </div>

                <!-- Live Seller KPI Cards -->
                <div class="pm-kpi-grid">
                    <div class="pm-card pm-kpi-card">
                        <span class="pm-stat-icon is-success"><?= icon('wallet', 20) ?></span>
                        <div>
                            <strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['available_balance'])) ?></strong>
                            <span class="pm-kpi-label">Available Balance</span>
                        </div>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <span class="pm-stat-icon"><?= icon('trending-up', 20) ?></span>
                        <div>
                            <strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['total_earnings'])) ?></strong>
                            <span class="pm-kpi-label">Total Net Earned</span>
                        </div>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <span class="pm-stat-icon is-warning"><?= icon('shopping-bag', 20) ?></span>
                        <div>
                            <strong class="pm-kpi-value pm-tabular"><?= number_format((int)$pStats['total_sales']) ?></strong>
                            <span class="pm-kpi-label">Verified Sales</span>
                        </div>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <span class="pm-stat-icon"><?= icon('package', 20) ?></span>
                        <div>
                            <strong class="pm-kpi-value pm-tabular"><?= (int)$pStats['approved'] ?> / <?= (int)$pStats['total'] ?></strong>
                            <span class="pm-kpi-label">Live / Total Projects</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 class="pm-h3">Recent Verified Sales</h2>
                        <a href="<?= e(url('seller/sales.php')) ?>" class="pm-link" style="font-size:0.83rem;">All Sales</a>
                    </div>
                    <?php if (empty($sales)): ?>
                        <p class="pm-muted" style="padding:18px 0;">No completed sales yet. Upload a high-quality project with screenshots to start earning.</p>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead><tr><th>Order</th><th>Project</th><th>Buyer</th><th>Gross</th><th>Fee</th><th>Net Earned</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><code><?= e($s['order_number']) ?></code></td>
                                            <td><strong><?= e($s['project_title']) ?></strong></td>
                                            <td><?= e($s['buyer_name']) ?></td>
                                            <td class="pm-tabular"><?= e(format_price((string)$s['final_price'])) ?></td>
                                            <td class="pm-tabular pm-muted">-<?= e(format_price((string)$s['commission_amount'])) ?></td>
                                            <td class="pm-tabular" style="font-weight:700;color:var(--success);">+<?= e(format_price((string)$s['seller_earning'])) ?></td>
                                            <td><?= e(format_date((string)$s['paid_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Projects -->
                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 class="pm-h3">My Projects</h2>
                        <a href="<?= e(url('seller/projects.php')) ?>" class="pm-link" style="font-size:0.83rem;">Manage All</a>
                    </div>
                    <?php if (empty($myProjects)): ?>
                        <div class="pm-empty" style="padding:28px 16px;">
                            <p class="pm-muted">You haven't published any projects yet.</p>
                            <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary" style="margin-top:8px;">
                                <?= icon('upload', 16) ?><span>Upload Your First Project</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead><tr><th>Project</th><th>Price</th><th>Sales</th><th>Views</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                                <tbody>
                                    <?php foreach ($myProjects as $pr): ?>
                                        <tr>
                                            <td><strong><?= e($pr['title']) ?></strong><small class="pm-muted" style="display:block;"><?= e($pr['category_name']) ?> · v<?= e($pr['version']) ?></small></td>
                                            <td class="pm-tabular"><?= (int)$pr['is_free'] === 1 ? 'Free' : e(format_price(effective_project_price($pr))) ?></td>
                                            <td class="pm-tabular"><?= (int)$pr['sales_count'] ?></td>
                                            <td class="pm-tabular"><?= (int)$pr['views_count'] ?></td>
                                            <td><?= render_status_badge((string)$pr['status']) ?></td>
                                            <td style="text-align:right;"><a href="<?= e(url('seller/edit-project.php?id=' . (int)$pr['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a></td>
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

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
