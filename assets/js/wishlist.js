/* ============================================================================
   Wishlist AJAX Toggle Controller
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-wishlist-toggle]');
        if (!btn || btn.dataset.busy === '1') return;
        e.preventDefault();

        const projectId = btn.getAttribute('data-wishlist-toggle');
        if (!projectId) return;

        btn.dataset.busy = '1';
        try {
            const res = await window.PM.post('actions/wishlist/toggle.php', { project_id: projectId });
            if (res.success) {
                document.querySelectorAll('[data-wishlist-toggle="' + projectId + '"]').forEach((el) => {
                    el.classList.toggle('is-active', !!res.in_wishlist);
                    el.setAttribute('aria-pressed', res.in_wishlist ? 'true' : 'false');
                    const labelEl = el.querySelector('[data-wishlist-label]');
                    if (labelEl) {
                        labelEl.textContent = res.in_wishlist ? 'Saved in Wishlist' : 'Add to Wishlist';
                    }
                });
                window.PM.updateCounter('[data-wishlist-count]', res.wishlist_count);
                window.PM.toast(res.message, 'success');

                // If removing from dedicated buyer/wishlist.php page, remove card smoothly
                if (!res.in_wishlist && btn.hasAttribute('data-remove-card-on-unsave')) {
                    const card = btn.closest('.pm-project-card');
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.96)';
                        setTimeout(() => {
                            card.remove();
                            if (!document.querySelector('[data-wishlist-grid] .pm-project-card')) {
                                window.location.reload();
                            }
                        }, 180);
                    }
                }
            } else if (!res.requires_auth) {
                window.PM.toast(res.message || 'Could not update wishlist.', 'error');
            }
        } catch {
            window.PM.toast('Connection error. Please try again.', 'error');
        } finally {
            delete btn.dataset.busy;
        }
    });
})();
