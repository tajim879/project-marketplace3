<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$faqs = db()->query("SELECT * FROM `faqs` WHERE `status` = 'active' ORDER BY `category` ASC, `sort_order` ASC")->fetchAll();
$grouped = [];
foreach ($faqs as $f) {
    $grouped[(string)$f['category']][] = $f;
}

$pageTitle = 'Frequently Asked Questions';
$pageDescription = 'Answers on bKash, Nagad & Rocket manual payments, secure ZIP downloads, licensing, commissions, and selling on CodeVault.';
$pageStyles = ['homepage.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container" style="text-align:center;max-width:740px;">
        <span class="pm-eyebrow" style="margin-bottom:12px;"><?= icon('help-circle', 14) ?><span>Help Center</span></span>
        <h1 class="pm-h1">Frequently Asked Questions</h1>
        <p class="pm-muted" style="margin-top:8px;">Clear guidance on licensing, manual bKash/Nagad/Rocket verification, secure downloads, and becoming a seller.</p>
        <div class="pm-input-wrap" style="max-width:500px;margin:20px auto 0;">
            <?= icon('search', 18) ?>
            <input type="search" id="pmFaqSearch" class="pm-input" placeholder="Filter questions instantly...">
        </div>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container" style="max-width:900px;display:grid;gap:34px;">
        <?php foreach ($grouped as $catName => $list): ?>
            <div style="display:grid;gap:12px;">
                <h2 class="pm-h3" style="display:inline-flex;align-items:center;gap:8px;color:var(--primary);">
                    <?= icon('bookmark', 17) ?><span><?= e($catName) ?></span>
                </h2>
                <?php foreach ($list as $idx => $faq): ?>
                    <div class="pm-faq-item <?= $idx === 0 ? 'is-open' : '' ?>" data-faq-searchable>
                        <button type="button" class="pm-faq-trigger" data-faq-toggle aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                            <span><?= e($faq['question']) ?></span>
                            <span class="pm-faq-chevron"><?= icon('chevron-down', 16) ?></span>
                        </button>
                        <div class="pm-faq-panel"><?= e($faq['answer']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="pm-card pm-card-pad" style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;">
            <div>
                <h3 class="pm-h3">Still need a hand?</h3>
                <p class="pm-muted">Open a tracked support ticket and our engineering support desk will assist you.</p>
            </div>
            <a href="<?= e(url('contact.php')) ?>" class="pm-btn pm-btn-primary">
                <?= icon('mail', 16) ?><span>Contact Support</span>
            </a>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
