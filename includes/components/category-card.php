<?php
declare(strict_types=1);

/**
 * Reusable Category Card Component — compact tile, 2 per row on every screen.
 * Expects array $category.
 */
$cUrl = url('category.php?slug=' . urlencode((string)$category['slug']));
$count = (int)($category['projects_count'] ?? 0);
$hasMedia = in_array((string)($category['icon_type'] ?? 'icon'), ['image', 'url'], true)
    && trim((string)($category['icon_image'] ?? '')) !== '';
$accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($category['accent_color'] ?? '')) ? (string)$category['accent_color'] : '#635BFF';
?>
<a href="<?= e($cUrl) ?>" class="pm-cat-card<?= $hasMedia ? ' has-media' : '' ?>" style="--cat-accent:<?= e($accent) ?>;">
    <div class="pm-cat-top">
        <span class="pm-cat-icon"><?= category_icon_html($category, 22) ?></span>
        <span class="pm-cat-count"><?= $count ?> <?= $count === 1 ? 'item' : 'items' ?></span>
    </div>
    <div class="pm-cat-body">
        <h3><?= e($category['name']) ?></h3>
        <?php if (!empty($category['short_description'])): ?>
            <p><?= e($category['short_description']) ?></p>
        <?php endif; ?>
    </div>
</a>
