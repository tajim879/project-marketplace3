<?php
declare(strict_types=1);

/**
 * Reusable Pagination Component
 * Expects int $currentPage, int $totalPages, string $paginationBasePath.
 */
if (($totalPages ?? 1) > 1):
    $curr = max(1, (int)$currentPage);
    $max = (int)$totalPages;
    $start = max(1, $curr - 2);
    $end = min($max, $curr + 2);
?>
<nav class="pm-pagination" aria-label="Pagination">
    <a class="pm-page-link <?= $curr <= 1 ? 'is-disabled' : '' ?>"
       href="<?= e($curr > 1 ? build_query_url($paginationBasePath, ['page' => $curr - 1]) : '#') ?>"
       aria-label="Previous page">
        <?= icon('chevron-left', 16) ?><span>Prev</span>
    </a>

    <?php if ($start > 1): ?>
        <a class="pm-page-link" href="<?= e(build_query_url($paginationBasePath, ['page' => 1])) ?>">1</a>
        <?php if ($start > 2): ?><span class="pm-muted" style="padding-inline:4px;">...</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($p = $start; $p <= $end; $p++): ?>
        <a class="pm-page-link <?= $p === $curr ? 'is-active' : '' ?>"
           href="<?= e(build_query_url($paginationBasePath, ['page' => $p])) ?>"
           <?= $p === $curr ? 'aria-current="page"' : '' ?>>
            <?= $p ?>
        </a>
    <?php endfor; ?>

    <?php if ($end < $max): ?>
        <?php if ($end < $max - 1): ?><span class="pm-muted" style="padding-inline:4px;">...</span><?php endif; ?>
        <a class="pm-page-link" href="<?= e(build_query_url($paginationBasePath, ['page' => $max])) ?>"><?= $max ?></a>
    <?php endif; ?>

    <a class="pm-page-link <?= $curr >= $max ? 'is-disabled' : '' ?>"
       href="<?= e($curr < $max ? build_query_url($paginationBasePath, ['page' => $curr + 1]) : '#') ?>"
       aria-label="Next page">
        <span>Next</span><?= icon('chevron-right', 16) ?>
    </a>
</nav>
<?php endif; ?>
