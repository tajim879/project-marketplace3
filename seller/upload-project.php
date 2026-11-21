<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$categories = global_categories(false);
$maxZip = (int)site_setting('max_project_zip_mb', '100');
$maxImg = (int)site_setting('max_image_mb', '5');
$isFreeOld = old('is_free', '0') === '1';

$pageTitle = 'Upload Project — Seller Studio';
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

                <div>
                    <h1 class="pm-h2">Upload New Digital Project</h1>
                    <p class="pm-muted">All uploaded ZIP archives undergo automated security inspection and are stored privately outside public web access.</p>
                </div>

                <form method="post" action="<?= e(url('actions/seller/project-save.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:20px;" data-submit-lock>
                    <?= csrf_field() ?>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="title">Project Title</label>
                            <input type="text" id="title" name="title" class="pm-input" value="<?= e(old('title')) ?>" placeholder="e.g. SmartPOS — Multi-Branch PHP Inventory & Billing" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="pm-select" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)old('category_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="short_description">Short Summary (15–280 chars)</label>
                        <input type="text" id="short_description" name="short_description" maxlength="280" class="pm-input" value="<?= e(old('short_description')) ?>" placeholder="Concise overview shown on marketplace cards and SEO previews" required>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label" for="full_description">Full Technical Description</label>
                        <textarea id="full_description" name="full_description" class="pm-textarea" rows="6" placeholder="Describe architecture, modules, requirements, installation steps, and what buyers receive..." required><?= e(old('full_description')) ?></textarea>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="features">Key Features (one per line)</label>
                            <textarea id="features" name="features" class="pm-textarea" rows="4" placeholder="Role-based Admin & Staff Panel&#10;Printable Thermal & A4 Invoices&#10;Clean OOP PHP 8.2+ & SQLite/PDO"><?= e(old('features')) ?></textarea>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="technologies">Technologies Used (comma separated)</label>
                            <textarea id="technologies" name="technologies" class="pm-textarea" rows="4" placeholder="PHP 8.2, SQLite, HTML5, CSS3, JavaScript"><?= e(old('technologies')) ?></textarea>
                        </div>
                    </div>

                    <!-- Pricing & License -->
                    <div style="padding:18px;border-radius:16px;background:var(--bg);border:1px solid var(--border);display:grid;gap:16px;">
                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="is_free_toggle">Pricing Model</label>
                                <select id="is_free_toggle" name="is_free" class="pm-select">
                                    <option value="0" <?= !$isFreeOld ? 'selected' : '' ?>>Paid License (BDT ৳)</option>
                                    <option value="1" <?= $isFreeOld ? 'selected' : '' ?>>Free Community Download (৳0)</option>
                                </select>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="license_type">Default License Type</label>
                                <select id="license_type" name="license_type" class="pm-select">
                                    <option value="regular">Regular Commercial License</option>
                                    <option value="extended">Extended SaaS License</option>
                                    <option value="mit">MIT Open License (for Free)</option>
                                    <option value="free_personal">Free Personal/Educational</option>
                                </select>
                            </div>
                        </div>

                        <div id="pmPaidPricingFields" class="pm-grid-3" style="display:<?= $isFreeOld ? 'none' : 'grid' ?>;">
                            <div class="pm-field">
                                <label class="pm-label" for="price">Regular Price (৳ BDT)</label>
                                <input type="number" step="0.01" min="50" id="price" name="price" class="pm-input pm-tabular" value="<?= e(old('price')) ?>" placeholder="1500" data-paid-required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="discount_price">Discount Price (৳ Optional)</label>
                                <input type="number" step="0.01" min="10" id="discount_price" name="discount_price" class="pm-input pm-tabular" value="<?= e(old('discount_price')) ?>" placeholder="1200">
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="extended_price">Extended Price (৳ Optional)</label>
                                <input type="number" step="0.01" min="100" id="extended_price" name="extended_price" class="pm-input pm-tabular" value="<?= e(old('extended_price')) ?>" placeholder="Auto 3.5x if blank">
                            </div>
                        </div>
                    </div>

                    <!-- Release & Metadata -->
                    <div class="pm-grid-3">
                        <div class="pm-field">
                            <label class="pm-label" for="version">Version Number</label>
                            <input type="text" id="version" name="version" class="pm-input" value="<?= e(old('version', '1.0.0')) ?>" required>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="compatibility">Compatibility</label>
                            <input type="text" id="compatibility" name="compatibility" class="pm-input" value="<?= e(old('compatibility')) ?>" placeholder="PHP 8.2+, Modern Browsers">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="support_months">Included Support (Months)</label>
                            <input type="number" min="0" max="36" id="support_months" name="support_months" class="pm-input" value="<?= e(old('support_months', '6')) ?>">
                        </div>
                    </div>

                    <div class="pm-grid-3">
                        <div class="pm-field">
                            <label class="pm-label" for="demo_url">Live Demo URL (Optional)</label>
                            <input type="url" id="demo_url" name="demo_url" class="pm-input" value="<?= e(old('demo_url')) ?>" placeholder="https://demo.example.com">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="video_preview_url">Video Preview URL (Optional)</label>
                            <input type="url" id="video_preview_url" name="video_preview_url" class="pm-input" value="<?= e(old('video_preview_url')) ?>" placeholder="https://youtube.com/...">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="documentation_url">Online Docs URL (Optional)</label>
                            <input type="url" id="documentation_url" name="documentation_url" class="pm-input" value="<?= e(old('documentation_url')) ?>" placeholder="https://docs.example.com">
                        </div>
                    </div>

                    <!-- Media & Private ZIP Package -->
                    <div class="pm-grid-3">
                        <div class="pm-field">
                            <label class="pm-label" for="thumbnail">Cover Thumbnail (JPG/PNG/WebP)</label>
                            <input type="file" id="thumbnail" name="thumbnail" class="pm-input" accept="image/jpeg,image/png,image/webp" required>
                            <small class="pm-field-hint">Recommended 1280×800 · Max <?= $maxImg ?> MB</small>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="screenshots">Gallery Screenshots (Up to 8)</label>
                            <input type="file" id="screenshots" name="screenshots[]" class="pm-input" accept="image/jpeg,image/png,image/webp" multiple>
                            <small class="pm-field-hint">Optional UI previews · Max <?= $maxImg ?> MB each</small>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="project_zip">Main Project Archive (.ZIP)</label>
                            <input type="file" id="project_zip" name="project_zip" class="pm-input" accept=".zip,application/zip" required>
                            <small class="pm-field-hint">Stored privately · Max <?= $maxZip ?> MB</small>
                        </div>
                    </div>

                    <label style="display:inline-flex;align-items:center;gap:9px;font-size:0.85rem;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="documentation_included" value="1" checked style="accent-color:var(--primary);">
                        <span>Setup &amp; usage documentation is included inside the ZIP package</span>
                    </label>

                    <div class="pm-btn-row" style="justify-content:flex-end;padding-top:10px;border-top:1px solid var(--border);">
                        <button type="submit" name="submit_mode" value="draft" class="pm-btn pm-btn-secondary">
                            <?= icon('file-text', 16) ?><span>Save as Draft</span>
                        </button>
                        <button type="submit" name="submit_mode" value="publish" class="pm-btn pm-btn-primary pm-btn-lg">
                            <?= icon('upload', 18) ?>
                            <span><?= site_setting('project_auto_approve', '0') === '1' ? 'Publish Project Now' : 'Submit Project for Review' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
