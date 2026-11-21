<?php
declare(strict_types=1);

$footSiteName = site_setting('site_name', 'CodeVault');
$footSiteTag = site_setting('site_tagline', 'Digital Project Marketplace');
$footDesc = site_setting('site_description', '');
$footEmail = site_setting('contact_email', 'support@codevault.market');
$footPhone = site_setting('contact_phone', '+880 1711-004488');
$footMarket = get_menu_items('footer_marketplace');
$footCompany = get_menu_items('footer_company');
$footLegal = get_menu_items('footer_legal');
?>
</main>

<footer class="pm-footer">
    <div class="pm-container">
        <div class="pm-footer-grid">
            <div class="pm-footer-about">
                <a href="<?= e(url('index.php')) ?>" class="pm-brand">
                    <?= brand_logo($footSiteName, true, $footSiteTag) ?>
                </a>
                <p class="pm-muted"><?= e($footDesc) ?></p>
                <div>
                    <span class="pm-footer-pay-label">Accepted Payments (BDT ৳)</span>
                    <div class="pm-pay-strip">
                        <?= payment_brand_badge('bkash', 'md') ?>
                        <?= payment_brand_badge('nagad', 'md') ?>
                        <?= payment_brand_badge('rocket', 'md') ?>
                    </div>
                </div>
            </div>

            <div class="pm-footer-col">
                <h3>Marketplace</h3>
                <ul class="pm-footer-links">
                    <?php foreach ($footMarket as $lnk):
                        if (str_contains((string)$lnk['url'], 'admin')) continue; ?>
                        <li><a href="<?= e(url((string)$lnk['url'])) ?>"><?= e($lnk['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="pm-footer-col">
                <h3>Company &amp; Help</h3>
                <ul class="pm-footer-links">
                    <?php foreach ($footCompany as $lnk):
                        if (str_contains((string)$lnk['url'], 'admin')) continue; ?>
                        <li><a href="<?= e(url((string)$lnk['url'])) ?>"><?= e($lnk['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="pm-footer-col">
                <h3>Legal &amp; Policies</h3>
                <ul class="pm-footer-links">
                    <?php foreach ($footLegal as $lnk):
                        if (str_contains((string)$lnk['url'], 'admin')) continue; ?>
                        <li><a href="<?= e(url((string)$lnk['url'])) ?>"><?= e($lnk['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="pm-footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($footSiteName) ?>. All rights reserved.</span>
            <div class="pm-footer-contact">
                <span><?= icon('mail', 14) ?><?= e($footEmail) ?></span>
                <span><?= icon('phone', 14) ?><?= e($footPhone) ?></span>
            </div>
        </div>
    </div>
</footer>

<div id="pmToastStack" class="pm-toast-stack" aria-live="polite"></div>

<script src="<?= e(asset_url('js/ajax.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/search.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/wishlist.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/cart.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/notifications.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/validation.js')) ?>" defer></script>
<?php foreach (($extraScripts ?? []) as $jsFile): ?>
<script src="<?= e(asset_url('js/' . ltrim($jsFile, '/'))) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php clear_old_input(); ?>
