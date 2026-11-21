/* ============================================================================
   Global UI Controller - Dropdowns, Drawers, Modals, FAQ, Copy & Newsletter
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // Dropdown menus (Notifications & Account Profile)
        const dropdowns = document.querySelectorAll('[data-dropdown]');
        dropdowns.forEach((dd) => {
            const btn = dd.querySelector('[data-dropdown-toggle]');
            if (!btn) return;
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = !dd.classList.contains('is-open');
                dropdowns.forEach((other) => {
                    other.classList.remove('is-open');
                    other.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
                });
                if (willOpen) {
                    dd.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', (e) => {
            dropdowns.forEach((dd) => {
                if (!dd.contains(e.target)) {
                    dd.classList.remove('is-open');
                    dd.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Slide-out drawers (Mobile Nav, Filter Sidebar, Buyer Sidebar)
        const backdrop = document.getElementById('pmDrawerBackdrop');
        function closeAllDrawers() {
            document.querySelectorAll('.pm-mobile-drawer.is-open, .pm-filter-sidebar.is-open, .pm-dash-sidebar.is-open')
                .forEach((el) => el.classList.remove('is-open'));
            backdrop?.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-drawer-open]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-drawer-open');
                const target = id ? document.getElementById(id) : null;
                if (!target) return;
                target.classList.add('is-open');
                backdrop?.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            });
        });

        document.querySelectorAll('[data-drawer-close]').forEach((btn) => {
            btn.addEventListener('click', closeAllDrawers);
        });
        backdrop?.addEventListener('click', closeAllDrawers);

        // Accessible Modals
        window.PM.openModal = function (id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            setTimeout(() => modal.querySelector('input:not([type="hidden"]), textarea, button')?.focus(), 60);
        };

        window.PM.closeModal = function (modal) {
            const el = typeof modal === 'string' ? document.getElementById(modal) : modal;
            if (!el) return;
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-modal-open]').forEach((btn) => {
            btn.addEventListener('click', () => window.PM.openModal(btn.getAttribute('data-modal-open')));
        });

        document.querySelectorAll('[data-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => window.PM.closeModal(btn.closest('.pm-modal')));
        });

        document.querySelectorAll('.pm-modal').forEach((modal) => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) window.PM.closeModal(modal);
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAllDrawers();
                document.querySelectorAll('.pm-modal.is-open').forEach((m) => window.PM.closeModal(m));
                dropdowns.forEach((dd) => dd.classList.remove('is-open'));
            }
        });

        // FAQ Accordion
        document.querySelectorAll('[data-faq-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.pm-faq-item');
                if (!item) return;
                const open = item.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        // Password Visibility Toggle (SVG eye / eye-off)
        document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-toggle-password');
                const input = id ? document.getElementById(id) : null;
                if (!input) return;
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                const showIcon = btn.querySelector('.icon-show');
                const hideIcon = btn.querySelector('.icon-hide');
                if (showIcon && hideIcon) {
                    showIcon.style.display = showing ? '' : 'none';
                    hideIcon.style.display = showing ? 'none' : '';
                }
            });
        });

        // Copy to Clipboard
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;
            const text = btn.getAttribute('data-copy') || '';
            if (!text) return;
            navigator.clipboard?.writeText(text).then(() => {
                window.PM.toast('Copied to clipboard', 'success');
            }).catch(() => {
                window.PM.toast(text, 'info');
            });
        });

        // Newsletter AJAX Form
        const newsForm = document.getElementById('pmNewsletterForm');
        if (newsForm) {
            newsForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const emailInput = newsForm.querySelector('input[name="email"]');
                const submitBtn = newsForm.querySelector('button[type="submit"]');
                if (!emailInput || !emailInput.value.trim()) return;
                submitBtn?.classList.add('is-loading');
                try {
                    const res = await window.PM.post('actions/support/newsletter.php', { email: emailInput.value.trim() });
                    window.PM.toast(res.message || 'Subscribed successfully.', res.success ? 'success' : 'error');
                    if (res.success) emailInput.value = '';
                } catch {
                    window.PM.toast('Could not subscribe right now. Please try again.', 'error');
                } finally {
                    submitBtn?.classList.remove('is-loading');
                }
            });
        }
    });
})();
