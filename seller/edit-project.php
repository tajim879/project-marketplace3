<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$pid = (int)($_GET['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM `projects` WHERE `id` = ? LIMIT 1");
$stmt->execute([$pid]);
$project = $stmt->fetch();

if (!$project || !can_manage_project($user, $project)) {
    flash('error', 'Project not found.');
    redirect(url('seller/projects.php'));
}

$fStmt = $pdo->prepare("SELECT `feature_text` FROM `project_features` WHERE `project_id` = ? ORDER BY `sort_order` ASC");
$fStmt->execute([$pid]);
$featuresText = implode("\n", $fStmt->fetchAll(PDO::FETCH_COLUMN));

$tStmt = $pdo->prepare("SELECT `tech_name` FROM `project_technologies` WHERE `project_id` = ? ORDER BY `id` ASC");
$tStmt->execute([$pid]);
$techText = implode(', ', $tStmt->fetchAll(PDO::FETCH_COLUMN));

$iStmt = $pdo->prepare("SELECT * FROM `project_images` WHERE `project_id` = ? ORDER BY `sort_order` ASC");
$iStmt->execute([$pid]);
$screenshots = $iStmt->fetchAll();

$categories = global_categories(false);
$isFree = (int)$project['is_free'] === 1;

$pageTitle = 'Edit Project — ' . $project['title'];
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

                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h1 class="pm-h2">Edit Project</h1>
                        <p class="pm-muted"><?= e($project['title']) ?> · Current status: <?= render_status_badge((string)$project['status']) ?></p>
                    </div>
                    <a href="<?= e(url('seller/project-updates.php?project=' . $pid)) ?>" class="pm-btn pm-btn-secondary btn-sm">
                        <?= icon('upload', 15) ?><span>Upload New Version (v<?= e($project['version']) ?>)</span>
                    </a>
                </div>

                <?php if ($project['status'] === 'rejected' && !empty($project['rejection_reason'])): ?>
                    <div class="pm-alert pm-alert-danger">
                        <?= icon('alert-circle', 18) ?>
                        <span><strong>Moderator Feedback:</strong> <?= e($project['rejection_reason']) ?> — Update your project below and click "Save &amp; Resubmit".</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('actions/seller/project-save.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:18px;" data-submit-lock>
                    <?= csrf_field() ?>
                    <input type="hidden" name="project_id" value="<?= $pid ?>">

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="title">Project Title</label>
                            <input type="text" id="title" name="title" class="pm-input" value="<?= e($project['title']) ?>" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="pm-select" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)$project['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="short_description">Short Summary</label>
                        <input type="text" id="short_description" name="short_description" maxlength="280" class="pm-input" value="<?= e($project['short_description']) ?>" required>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="full_description">Full Description</label>
                        <textarea id="full_description" name="full_description" class="pm-textarea" rows="6" required><?= e($project['description']) ?></textarea>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="features">Key Features (one per line)</label>
                            <textarea id="features" name="features" class="pm-textarea" rows="4"><?= e($featuresText) ?></textarea>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="technologies">Technologies (comma separated)</label>
                            <textarea id="technologies" name="technologies" class="pm-textarea" rows="4"><?= e($techText) ?></textarea>
                        </div>
                    </div>

                    <div style="padding:18px;border-radius:16px;background:var(--bg);border:1px solid var(--border);display:grid;gap:14px;">
                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="is_free_toggle">Pricing Model</label>
                                <select id="is_free_toggle" name="is_free" class="pm-select">
                                    <option value="0" <?= !$isFree ? 'selected' : '' ?>>Paid License (BDT ৳)</option>
                                    <option value="1" <?= $isFree ? 'selected' : '' ?>>Free Download (৳0)</option>
                                </select>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="license_type">License Type</label>
                                <select id="license_type" name="license_type" class="pm-select">
                                    <?php foreach (['regular' => 'Regular Commercial', 'extended' => 'Extended SaaS', 'mit' => 'MIT Open', 'free_personal' => 'Free Personal'] as $lk => $ll): ?>
                                        <option value="<?= $lk ?>" <?= $project['license_type'] === $lk ? 'selected' : '' ?>><?= e($ll) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="pmPaidPricingFields" class="pm-grid-3" style="display:<?= $isFree ? 'none' : 'grid' ?>;">
                            <div class="pm-field">
                                <label class="pm-label" for="price">Regular Price (৳)</label>
                                <input type="number" step="0.01" id="price" name="price" class="pm-input pm-tabular" value="<?= e($project['price']) ?>" data-paid-required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="discount_price">Discount Price (৳)</label>
                                <input type="number" step="0.01" id="discount_price" name="discount_price" class="pm-input pm-tabular" value="<?= e((string)($project['discount_price'] ?? '')) ?>">
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="extended_price">Extended Price (৳)</label>
                                <input type="number" step="0.01" id="extended_price" name="extended_price" class="pm-input pm-tabular" value="<?= e((string)($project['extended_price'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="compatibility">Compatibility</label>
                            <input type="text" id="compatibility" name="compatibility" class="pm-input" value="<?= e((string)($project['compatibility'] ?? '')) ?>">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="support_months">Support Months</label>
                            <input type="number" min="0" max="36" id="support_months" name="support_months" class="pm-input" value="<?= (int)$project['support_months'] ?>">
                        </div>
                    </div>

                    <div class="pm-grid-3">
                        <div class="pm-field"><label class="pm-label" for="demo_url">Demo URL</label><input type="url" id="demo_url" name="demo_url" class="pm-input" value="<?= e((string)($project['demo_url'] ?? '')) ?>"></div>
                        <div class="pm-field"><label class="pm-label" for="video_preview_url">Video URL</label><input type="url" id="video_preview_url" name="video_preview_url" class="pm-input" value="<?= e((string)($project['video_preview_url'] ?? '')) ?>"></div>
                        <div class="pm-field"><label class="pm-label" for="documentation_url">Docs URL</label><input type="url" id="documentation_url" name="documentation_url" class="pm-input" value="<?= e((string)($project['documentation_url'] ?? '')) ?>"></div>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="thumbnail">Replace Thumbnail (Optional)</label>
                            <input type="file" id="thumbnail" name="thumbnail" class="pm-input" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="screenshots">Add More Screenshots</label>
                            <input type="file" id="screenshots" name="screenshots[]" class="pm-input" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>
                    </div>

                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;">
                        <input type="checkbox" name="documentation_included" value="1" <?= (int)$project['documentation_included'] === 1 ? 'checked' : '' ?>>
                        <span>Documentation included inside ZIP</span>
                    </label>

                    <div class="pm-btn-row" style="justify-content:flex-end;">
                        <button type="submit" name="submit_mode" value="draft" class="pm-btn pm-btn-secondary">Save as Draft</button>
                        <button type="submit" name="submit_mode" value="publish" class="pm-btn pm-btn-primary">
                            <?= icon('check', 17) ?>
                            <span><?= in_array((string)$project['status'], ['draft', 'rejected'], true) ? 'Save & Submit for Review' : 'Save Changes' ?></span>
                        </button>
                    </div>
                </form>

                <?php if (!empty($screenshots)): ?>
                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                        <h2 class="pm-h3">Current Gallery Screenshots (<?= count($screenshots) ?>)</h2>
                        <div class="pm-grid-3">
                            <?php foreach ($screenshots as $img): ?>
                                <div style="border:1px solid var(--border);border-radius:12px;padding:10px;display:grid;gap:8px;">
                                    <img src="<?= e(media_url((string)$img['image_path'])) ?>" alt="Screenshot" style="width:100%;height:130px;object-fit:cover;border-radius:8px;">
                                    <form method="post" action="<?= e(url('actions/seller/engage.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_screenshot">
                                        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                        <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm pm-btn-block" style="color:var(--danger);">
                                            <?= icon('trash', 14) ?><span>Remove</span>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
