/* ============================================================================
   Client-Side Form Validation, Password Strength & Role Switcher
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const strengthInput = document.querySelector('[data-password-strength]');
        const fill = document.getElementById('pmStrengthFill');
        const label = document.getElementById('pmStrengthLabel');

        if (strengthInput && fill) {
            strengthInput.addEventListener('input', () => {
                const v = strengthInput.value;
                let score = 0;
                if (v.length >= 8) score++;
                if (v.length >= 12) score++;
                if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
                if (/[0-9]/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v)) score++;

                const pct = Math.min(100, score * 20);
                fill.style.width = pct + '%';
                const color = score <= 1 ? 'var(--danger)' : score <= 3 ? 'var(--warning)' : 'var(--success)';
                fill.style.background = color;
                if (label) {
                    label.textContent = v.length === 0
                        ? 'Use 8+ characters with letters and numbers'
                        : score <= 1 ? 'Weak password' : score <= 3 ? 'Good password' : 'Strong password';
                }
            });
        }

        // Buyer / Seller role switcher on registration form
        const roleRadios = document.querySelectorAll('input[name="account_type"]');
        const sellerPane = document.getElementById('pmSellerRegPane');
        const submitLabel = document.getElementById('pmRegSubmitLabel');
        if (roleRadios.length && sellerPane) {
            const syncRole = () => {
                const selected = document.querySelector('input[name="account_type"]:checked');
                const isSeller = selected && selected.value === 'seller';
                sellerPane.classList.toggle('is-visible', !!isSeller);
                sellerPane.querySelectorAll('[data-seller-required]').forEach((input) => {
                    input.required = !!isSeller;
                });
                // Buyer account: disable all seller fields so they are never validated or submitted
                sellerPane.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !isSeller;
                });
                sellerPane.setAttribute('aria-hidden', isSeller ? 'false' : 'true');
                if (submitLabel) {
                    submitLabel.textContent = isSeller ? 'Create Seller Account' : 'Create Buyer Account';
                }
            };
            roleRadios.forEach((r) => r.addEventListener('change', syncRole));
            syncRole();
        }

        // Manual payment method instructions switcher (payment.php)
        const payRadios = document.querySelectorAll('input[name="payment_method_id"][data-method-panel]');
        if (payRadios.length) {
            const syncMethod = () => {
                const active = document.querySelector('input[name="payment_method_id"]:checked');
                const targetId = active ? active.getAttribute('data-method-panel') : '';
                document.querySelectorAll('[data-payment-detail]').forEach((box) => {
                    box.style.display = (box.id === targetId) ? 'grid' : 'none';
                });
            };
            payRadios.forEach((r) => r.addEventListener('change', syncMethod));
            syncMethod();
        }

        // Free vs Paid pricing switcher (upload/edit project)
        const freeToggle = document.getElementById('is_free_toggle');
        const paidFields = document.getElementById('pmPaidPricingFields');
        if (freeToggle && paidFields) {
            const syncPricing = () => {
                const isFree = freeToggle.value === '1' || freeToggle.checked;
                paidFields.style.display = isFree ? 'none' : 'grid';
                paidFields.querySelectorAll('[data-paid-required]').forEach((el) => {
                    el.required = !isFree;
                });
            };
            freeToggle.addEventListener('change', syncPricing);
            syncPricing();
        }

        // Loading state on standard form submissions
        document.querySelectorAll('form[data-submit-lock]').forEach((form) => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) btn.classList.add('is-loading');
            });
        });
    });
})();
