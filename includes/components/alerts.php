<?php
declare(strict_types=1);

/**
 * Reusable Flash Alert Banner Component
 */
$flashes = get_flashes();
if (!empty($flashes)):
    foreach ($flashes as $f):
        $type = (string)($f['type'] ?? 'info');
        $iconName = match ($type) {
            'success' => 'check-circle',
            'error', 'danger' => 'alert-circle',
            'warning' => 'alert-circle',
            default => 'info',
        };
?>
<div class="pm-alert pm-alert-<?= e($type) ?>" role="alert">
    <?= icon($iconName, 19) ?>
    <span><?= e($f['message'] ?? '') ?></span>
</div>
<?php
    endforeach;
endif;
?>
