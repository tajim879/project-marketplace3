<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Users & Roles Management';
require INCLUDES_PATH . '/layouts/admin-header.php';

$role = trim((string)($_GET['role'] ?? 'all'));
$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT u.*, (SELECT COUNT(*) FROM `orders` o WHERE o.buyer_id = u.id AND o.status = 'paid') AS paid_orders
        FROM `users` u WHERE 1=1";
$params = [];
if (in_array($role, ['buyer', 'seller', 'admin'], true)) {
    $sql .= " AND u.role = ?";
    $params[] = $role;
}
if ($q !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY u.id DESC LIMIT 200";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Users &amp; Roles Governance</h1>
        <p>Manage all Buyer, Seller, and Admin accounts, control account status, assign roles, reset passwords, or add new accounts.</p>
    </div>
    <details class="pm-inline-action">
        <summary class="pm-btn pm-btn-primary"><?= icon('plus', 16) ?><span>Create Account</span></summary>
        <form method="post" action="<?= e(url('actions/admin/users.php')) ?>" class="pm-card pm-card-pad" style="margin-top:10px;display:grid;gap:10px;min-width:min(320px,100%);">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <input type="text" name="full_name" class="pm-input" placeholder="Full Name" required>
            <input type="text" name="username" class="pm-input" placeholder="Username" required>
            <input type="email" name="email" class="pm-input" placeholder="Email Address" required>
            <input type="tel" name="phone" class="pm-input" placeholder="Phone (optional)">
            <select name="role" class="pm-select">
                <option value="buyer">Buyer Account</option>
                <option value="seller">Seller Account</option>
                <option value="admin">Administrator Account</option>
            </select>
            <input type="password" name="password" class="pm-input" placeholder="Password (8+ chars & number)" required>
            <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Create User</button>
        </form>
    </details>
</div>

<div class="pm-card pm-card-pad pm-admin-toolbar">
    <div class="pm-admin-tabs">
        <?php foreach (['all' => 'All Accounts', 'buyer' => 'Buyers', 'seller' => 'Sellers', 'admin' => 'Admins'] as $rk => $rl): ?>
            <a href="<?= e(build_query_url('admin/users.php', ['role' => $rk])) ?>" class="pm-chip <?= $role === $rk ? 'is-active' : '' ?>"><?= e($rl) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="<?= e(url('admin/users.php')) ?>" class="pm-btn-row">
        <input type="hidden" name="role" value="<?= e($role) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" class="pm-input" placeholder="Name, @user, email..." style="width:220px;">
        <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Search</button>
    </form>
</div>

<div class="pm-card pm-card-pad">
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Purchases</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $isSelf = (int)$u['id'] === (int)$adminUser['id']; ?>
                    <tr>
                        <td>
                            <strong><?= e($u['full_name']) ?></strong><?= $isSelf ? ' <span class="pm-badge pm-badge-primary">You</span>' : '' ?>
                            <small class="pm-muted" style="display:block;">@<?= e($u['username']) ?> · <?= e($u['email']) ?><?= !empty($u['phone']) ? ' · ' . e($u['phone']) : '' ?></small>
                        </td>
                        <td>
                            <?php if ($isSelf): ?>
                                <?= render_status_badge((string)$u['role']) ?>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('actions/admin/users.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <select name="role" class="pm-select" style="width:110px;padding:6px 8px;" onchange="this.form.submit()">
                                        <?php foreach (['buyer', 'seller', 'admin'] as $rOpt): ?>
                                            <option value="<?= $rOpt ?>" <?= $u['role'] === $rOpt ? 'selected' : '' ?>><?= ucfirst($rOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="pm-tabular"><?= (int)$u['paid_orders'] ?></td>
                        <td><?= render_status_badge((string)$u['status']) ?></td>
                        <td><?= e(format_date((string)$u['created_at'])) ?></td>
                        <td style="text-align:right;">
                            <div class="pm-btn-row" style="justify-content:flex-end;">
                                <?php if (!$isSelf): ?>
                                    <form method="post" action="<?= e(url('actions/admin/users.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                        <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                                    </form>
                                <?php endif; ?>
                                <details class="pm-inline-action">
                                    <summary class="pm-btn pm-btn-ghost pm-btn-sm">Reset PW</summary>
                                    <form method="post" action="<?= e(url('actions/admin/users.php')) ?>" class="pm-inline-pop">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="password" name="new_password" class="pm-input" placeholder="New password (8+ chars)" minlength="8" required>
                                        <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Save Password</button>
                                    </form>
                                </details>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
