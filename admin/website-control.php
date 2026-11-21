<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Website Control Center';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$tab = trim((string)($_GET['tab'] ?? 'general'));
$sections = $pdo->query("SELECT * FROM `homepage_sections` ORDER BY `sort_order` ASC")->fetchAll();
$banners = $pdo->query("SELECT * FROM `banners` ORDER BY `sort_order` ASC, `id` DESC")->fetchAll();
$menus = $pdo->query("SELECT * FROM `menus` ORDER BY `id` ASC")->fetchAll();
$menuItems = $pdo->query(
    "SELECT mi.*, m.name AS menu_name, m.location FROM `menu_items` mi
     INNER JOIN `menus` m ON m.id = mi.menu_id
     ORDER BY m.id ASC, mi.sort_order ASC"
)->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Website Control Center</h1>
        <p>Manage site identity, custom logo &amp; favicon, SEO, dynamic homepage sections, promotional banners, menus, maintenance mode, and upload limits — without editing code.</p>
    </div>
    <a href="<?= e(url('index.php')) ?>" target="_blank" class="pm-btn pm-btn-secondary"><?= icon('external-link', 15) ?><span>Preview Live Site</span></a>
</div>

<div class="pm-admin-tabs">
    <?php foreach ([
        'general' => 'Branding, SEO & Footer',
        'homepage' => 'Homepage & Hero Builder',
        'banners' => 'Promotional Banners',
        'menus' => 'Navigation Menus',
        'system' => 'Maintenance & Upload Limits',
    ] as $tk => $tl): ?>
        <a href="<?= e(url('admin/website-control.php?tab=' . $tk)) ?>" class="pm-chip <?= $tab === $tk ? 'is-active' : '' ?>"><?= e($tl) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'homepage'): ?>
    <form method="post" action="<?= e(url('actions/admin/website.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:20px;">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="homepage">
        <h2 class="pm-h3">Hero Banner Content</h2>
        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Hero Badge</label><input type="text" name="hero_badge" class="pm-input" value="<?= e(site_setting('hero_badge')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Hero Headline</label><input type="text" name="hero_title" class="pm-input" value="<?= e(site_setting('hero_title')) ?>"></div>
        </div>
        <div class="pm-field"><label class="pm-label">Hero Subtitle</label><textarea name="hero_subtitle" class="pm-textarea" rows="3"><?= e(site_setting('hero_subtitle')) ?></textarea></div>
        <div class="pm-admin-kpi-grid">
            <div class="pm-field"><label class="pm-label">Primary CTA Text</label><input type="text" name="hero_primary_cta_label" class="pm-input" value="<?= e(site_setting('hero_primary_cta_label')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Primary CTA Link</label><input type="text" name="hero_primary_cta_url" class="pm-input" value="<?= e(site_setting('hero_primary_cta_url')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Secondary CTA Text</label><input type="text" name="hero_secondary_cta_label" class="pm-input" value="<?= e(site_setting('hero_secondary_cta_label')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Secondary CTA Link</label><input type="text" name="hero_secondary_cta_url" class="pm-input" value="<?= e(site_setting('hero_secondary_cta_url')) ?>"></div>
        </div>
        <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;">
            <input type="checkbox" name="show_stats_bar" value="1" <?= site_setting('show_stats_bar', '1') === '1' ? 'checked' : '' ?>>
            <span>Show Live Platform Stats Counter Bar under Hero</span>
        </label>

        <h2 class="pm-h3" style="padding-top:12px;border-top:1px solid var(--border);">Dynamic Homepage Sections (Enable / Reorder / Rename)</h2>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead><tr><th>Visible</th><th>Section Key</th><th>Title</th><th>Subtitle</th><th>Max Items</th><th>Order</th></tr></thead>
                <tbody>
                    <?php foreach ($sections as $sec): $sid = (int)$sec['id']; ?>
                        <tr>
                            <td><input type="checkbox" name="sections[<?= $sid ?>][is_visible]" value="1" <?= (int)$sec['is_visible'] ? 'checked' : '' ?>></td>
                            <td><code><?= e($sec['section_key']) ?></code><input type="hidden" name="sections[<?= $sid ?>][badge_text]" value="<?= e((string)$sec['badge_text']) ?>"></td>
                            <td><input type="text" name="sections[<?= $sid ?>][title]" class="pm-input" value="<?= e($sec['title']) ?>"></td>
                            <td><input type="text" name="sections[<?= $sid ?>][subtitle]" class="pm-input" value="<?= e((string)$sec['subtitle']) ?>"></td>
                            <td><input type="number" min="1" max="24" name="sections[<?= $sid ?>][items_limit]" class="pm-input pm-tabular" value="<?= (int)$sec['items_limit'] ?>" style="width:74px;"></td>
                            <td><input type="number" name="sections[<?= $sid ?>][sort_order]" class="pm-input pm-tabular" value="<?= (int)$sec['sort_order'] ?>" style="width:74px;"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div><button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 16) ?><span>Save Homepage Layout</span></button></div>
    </form>

<?php elseif ($tab === 'banners'): ?>
    <div class="pm-admin-split">
        <div class="pm-card pm-card-pad">
            <h2 class="pm-h3" style="margin-bottom:12px;">Active &amp; Scheduled Promo Banners</h2>
            <?php if (empty($banners)): ?>
                <p class="pm-muted">No banners configured.</p>
            <?php else: ?>
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead><tr><th>Position</th><th>Title</th><th>CTA</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($banners as $b): ?>
                                <tr>
                                    <td><code><?= e($b['position']) ?></code></td>
                                    <td><strong><?= e($b['title']) ?></strong><small class="pm-muted" style="display:block;"><?= e((string)$b['subtitle']) ?></small></td>
                                    <td><?= e((string)($b['primary_cta_label'] ?: '—')) ?></td>
                                    <td><?= render_status_badge((string)$b['status']) ?></td>
                                    <td style="text-align:right;">
                                        <form method="post" action="<?= e(url('actions/admin/website.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="section" value="banner_delete">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" onclick="return confirm('Delete banner?');"><?= icon('trash', 14) ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= e(url('actions/admin/website.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="banner_save">
            <h2 class="pm-h3">Add Promotional Banner</h2>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Position</label><input type="text" name="position" class="pm-input" value="homepage_promo"></div>
                <div class="pm-field"><label class="pm-label">Badge Label</label><input type="text" name="badge_text" class="pm-input" placeholder="Creator Spotlight"></div>
            </div>
            <div class="pm-field"><label class="pm-label">Headline</label><input type="text" name="title" class="pm-input" required></div>
            <div class="pm-field"><label class="pm-label">Subtitle</label><textarea name="subtitle" class="pm-textarea" rows="2"></textarea></div>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Primary Button Label</label><input type="text" name="primary_cta_label" class="pm-input" placeholder="Start Selling"></div>
                <div class="pm-field"><label class="pm-label">Primary Button URL</label><input type="text" name="primary_cta_url" class="pm-input" placeholder="register.php?role=seller"></div>
            </div>
            <button type="submit" class="pm-btn pm-btn-primary">Save Banner</button>
        </form>
    </div>

<?php elseif ($tab === 'menus'): ?>
    <div class="pm-admin-split">
        <div class="pm-card pm-card-pad">
            <h2 class="pm-h3" style="margin-bottom:12px;">Header &amp; Footer Navigation Links</h2>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Menu</th><th>Label</th><th>URL</th><th>Order</th><th>Visible</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($menuItems as $mi): ?>
                            <tr>
                                <td><span class="pm-badge"><?= e($mi['menu_name']) ?></span></td>
                                <td><strong><?= e($mi['label']) ?></strong></td>
                                <td><code><?= e($mi['url']) ?></code></td>
                                <td class="pm-tabular"><?= (int)$mi['sort_order'] ?></td>
                                <td><?= (int)$mi['is_visible'] ? '<span class="pm-badge pm-badge-success">Shown</span>' : '<span class="pm-badge">Hidden</span>' ?></td>
                                <td style="text-align:right;">
                                    <div class="pm-btn-row" style="justify-content:flex-end;">
                                        <form method="post" action="<?= e(url('actions/admin/website.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="section" value="menu_toggle">
                                            <input type="hidden" name="id" value="<?= (int)$mi['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm"><?= (int)$mi['is_visible'] ? 'Hide' : 'Show' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(url('actions/admin/website.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="section" value="menu_delete">
                                            <input type="hidden" name="id" value="<?= (int)$mi['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);"><?= icon('trash', 14) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="post" action="<?= e(url('actions/admin/website.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="menu_add">
            <h2 class="pm-h3">Add Navigation Link</h2>
            <div class="pm-field">
                <label class="pm-label">Target Menu</label>
                <select name="menu_id" class="pm-select" required>
                    <?php foreach ($menus as $mn): ?>
                        <option value="<?= (int)$mn['id'] ?>"><?= e($mn['name']) ?> (<?= e($mn['location']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pm-field"><label class="pm-label">Link Label</label><input type="text" name="label" class="pm-input" placeholder="e.g. Documentation" required></div>
            <div class="pm-field"><label class="pm-label">Link URL</label><input type="text" name="url" class="pm-input" placeholder="e.g. faq.php" required></div>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Sort Order</label><input type="number" name="sort_order" class="pm-input" value="10"></div>
                <div class="pm-field"><label class="pm-label">Open In</label><select name="target" class="pm-select"><option value="_self">Same Tab</option><option value="_blank">New Tab</option></select></div>
            </div>
            <button type="submit" class="pm-btn pm-btn-primary">Add Menu Item</button>
        </form>
    </div>

<?php elseif ($tab === 'system'): ?>
    <form method="post" action="<?= e(url('actions/admin/website.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="system">
        <h2 class="pm-h3">Platform Access, Maintenance &amp; Upload Limits</h2>

        <div style="display:grid;gap:12px;">
            <?php foreach ([
                'maintenance_mode' => ['Maintenance Mode (temporarily pauses public storefront; admins retain full access)', '0'],
                'user_registration_enabled' => ['Allow Public User Registration', '1'],
                'seller_registration_enabled' => ['Allow Direct Seller Account Registration & Applications', '1'],
                'seller_auto_approve' => ['Auto-Approve New Seller Signups Immediately', '1'],
                'project_auto_approve' => ['Auto-Approve Seller Project Uploads Without Moderation Queue', '0'],
                'notifications_enabled' => ['Enable In-App User Notification Bell', '1'],
                'newsletter_enabled' => ['Enable Footer Newsletter Subscription Box', '1'],
            ] as $key => [$label, $def]): ?>
                <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;background:var(--bg);border:1px solid var(--border);font-size:0.87rem;font-weight:600;cursor:pointer;">
                    <input type="checkbox" name="<?= $key ?>" value="1" <?= site_setting($key, $def) === '1' ? 'checked' : '' ?>>
                    <span><?= e($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="pm-grid-3">
            <div class="pm-field"><label class="pm-label">Max Project ZIP Size (MB)</label><input type="number" min="5" max="500" name="max_project_zip_mb" class="pm-input pm-tabular" value="<?= e(site_setting('max_project_zip_mb', '100')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Max Thumbnail / Screenshot (MB)</label><input type="number" min="1" max="25" name="max_image_mb" class="pm-input pm-tabular" value="<?= e(site_setting('max_image_mb', '5')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Max Payment Proof Image (MB)</label><input type="number" min="1" max="25" name="max_proof_mb" class="pm-input pm-tabular" value="<?= e(site_setting('max_proof_mb', '5')) ?>"></div>
        </div>

        <div><button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 16) ?><span>Save System Policies</span></button></div>
    </form>

<?php else: ?>
    <form method="post" action="<?= e(url('actions/admin/website.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="section" value="general">
        <h2 class="pm-h3">Site Identity &amp; Brand Assets</h2>

        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Marketplace Name</label><input type="text" name="site_name" class="pm-input" value="<?= e(site_setting('site_name')) ?>" required></div>
            <div class="pm-field"><label class="pm-label">Site Tagline</label><input type="text" name="site_tagline" class="pm-input" value="<?= e(site_setting('site_tagline')) ?>"></div>
        </div>

        <div class="pm-form-grid-2">
            <div class="pm-field">
                <label class="pm-label">Upload Custom Logo (PNG/WebP/JPG)</label>
                <input type="file" name="custom_logo" class="pm-input" accept="image/png,image/webp,image/jpeg">
                <?php if (site_setting('custom_logo_path') !== ''): ?>
                    <label style="font-size:0.78rem;display:inline-flex;align-items:center;gap:6px;margin-top:4px;"><input type="checkbox" name="reset_logo" value="1"> Reset to default vector emblem</label>
                <?php endif; ?>
            </div>
            <div class="pm-field">
                <label class="pm-label">Upload Custom Favicon (PNG/WebP)</label>
                <input type="file" name="custom_favicon" class="pm-input" accept="image/png,image/webp,image/jpeg">
                <?php if (site_setting('custom_favicon_path') !== ''): ?>
                    <label style="font-size:0.78rem;display:inline-flex;align-items:center;gap:6px;margin-top:4px;"><input type="checkbox" name="reset_favicon" value="1"> Reset to default favicon</label>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="pm-h3" style="padding-top:10px;border-top:1px solid var(--border);">Contact &amp; Footer Information</h2>
        <div class="pm-grid-3">
            <div class="pm-field"><label class="pm-label">Support Email</label><input type="email" name="contact_email" class="pm-input" value="<?= e(site_setting('contact_email')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Support Phone</label><input type="text" name="contact_phone" class="pm-input" value="<?= e(site_setting('contact_phone')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Office Address</label><input type="text" name="contact_address" class="pm-input" value="<?= e(site_setting('contact_address')) ?>"></div>
        </div>
        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Footer About Blurb</label><textarea name="footer_about" class="pm-textarea" rows="3"><?= e(site_setting('footer_about')) ?></textarea></div>
            <div class="pm-field"><label class="pm-label">Copyright Text</label><textarea name="copyright_text" class="pm-textarea" rows="3"><?= e(site_setting('copyright_text')) ?></textarea></div>
        </div>

        <h2 class="pm-h3" style="padding-top:10px;border-top:1px solid var(--border);">SEO Meta &amp; Social Profiles</h2>
        <div class="pm-form-grid-2">
            <div class="pm-field"><label class="pm-label">Default SEO Title</label><input type="text" name="meta_title" class="pm-input" value="<?= e(site_setting('meta_title')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Default SEO Meta Description</label><input type="text" name="meta_description" class="pm-input" value="<?= e(site_setting('meta_description')) ?>"></div>
        </div>
        <div class="pm-grid-3">
            <div class="pm-field"><label class="pm-label">GitHub URL</label><input type="url" name="github_url" class="pm-input" value="<?= e(site_setting('github_url')) ?>"></div>
            <div class="pm-field"><label class="pm-label">LinkedIn URL</label><input type="url" name="linkedin_url" class="pm-input" value="<?= e(site_setting('linkedin_url')) ?>"></div>
            <div class="pm-field"><label class="pm-label">YouTube URL</label><input type="url" name="youtube_url" class="pm-input" value="<?= e(site_setting('youtube_url')) ?>"></div>
        </div>

        <div><button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 16) ?><span>Save Branding &amp; SEO Settings</span></button></div>
    </form>
<?php endif; ?>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
