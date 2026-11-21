/* ============================================================================
   Shopping Cart & License Selector Controller
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // Live license selector update on Project Details page
        const licenseRadios = document.querySelectorAll('input[name="selected_license"]');
        const mainPriceLabel = document.getElementById('pmSelectedPriceLabel');
        const licenseHiddenInputs = document.querySelectorAll('[data-sync-license]');

        licenseRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                if (!radio.checked) return;
                if (mainPriceLabel && radio.dataset.formatted) {
                    mainPriceLabel.textContent = radio.dataset.formatted;
                }
                licenseHiddenInputs.forEach((inp) => { inp.value = radio.value; });
                document.querySelectorAll('[data-add-cart]').forEach((btn) => {
                    if (btn.hasAttribute('data-dynamic-license')) {
                        btn.setAttribute('data-license', radio.value);
                    }
                });
            });
        });

        // AJAX Add to Cart
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-add-cart]');
            if (!btn || btn.classList.contains('is-loading')) return;
            e.preventDefault();

            const projectId = btn.getAttribute('data-add-cart');
            const license = btn.getAttribute('data-license') || 'regular';
            const redirectToCart = btn.getAttribute('data-buy-now') === '1';

            btn.classList.add('is-loading');
            try {
                const res = await window.PM.post('actions/orders/cart.php', {
                    action: 'add',
                    project_id: projectId,
                    license_type: license
                });
                if (res.success) {
                    window.PM.updateCounter('[data-cart-count]', res.cart_count);
                    if (redirectToCart) {
                        window.location.href = window.PM.resolveUrl('checkout.php');
                        return;
                    }
                    window.PM.toast(res.message, 'success');
                } else {
                    window.PM.toast(res.message || 'Could not add project to cart.', 'info');
                }
            } catch {
                window.PM.toast('Network error while updating cart.', 'error');
            } finally {
                btn.classList.remove('is-loading');
            }
        });
    });
})();
