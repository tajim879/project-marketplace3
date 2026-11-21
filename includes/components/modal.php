<?php
declare(strict_types=1);

/**
 * Reusable License Certificate Modal (Used in Buyer Purchases & Downloads)
 */
?>
<div class="pm-modal" id="pmLicenseModal" aria-hidden="true" role="dialog" aria-label="Purchase License Certificate">
    <div class="pm-modal-dialog">
        <div class="pm-modal-head">
            <strong class="pm-h3" style="display:inline-flex;align-items:center;gap:8px;">
                <?= icon('shield', 20) ?><span>License Certificate</span>
            </strong>
            <button type="button" class="pm-icon-btn" style="width:34px;height:34px;" data-modal-close aria-label="Close modal">
                <?= icon('close', 16) ?>
            </button>
        </div>

        <div style="display:grid;gap:14px;">
            <div style="padding:14px 16px;border-radius:12px;background:var(--bg-inset);box-shadow:var(--shadow-inset);display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <small class="pm-muted" style="display:block;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;">License Key</small>
                    <strong id="pmLicKey" class="pm-mono" style="font-size:0.92rem;background:none;border:0;padding:0;">—</strong>
                </div>
                <button type="button" id="pmLicCopyBtn" class="pm-btn pm-btn-secondary pm-btn-sm" data-copy="">
                    <?= icon('copy', 15) ?><span>Copy</span>
                </button>
            </div>

            <dl class="pm-spec-list">
                <div class="pm-spec-row"><dt>Project</dt><dd id="pmLicProject">—</dd></div>
                <div class="pm-spec-row"><dt>License Tier</dt><dd id="pmLicType">—</dd></div>
                <div class="pm-spec-row"><dt>Order Reference</dt><dd id="pmLicOrder">—</dd></div>
                <div class="pm-spec-row"><dt>Issued Date</dt><dd id="pmLicDate">—</dd></div>
            </dl>

            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:8px;">
                <a href="<?= e(url('license-policy.php')) ?>" class="pm-btn pm-btn-ghost pm-btn-sm">Full License Terms</a>
                <button type="button" class="pm-btn pm-btn-primary pm-btn-sm" data-modal-close>Done</button>
            </div>
        </div>
    </div>
</div>
