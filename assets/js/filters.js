/* ============================================================================
   Marketplace Filter, Sort & Grid/List Layout Switcher
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // Auto-submit sort selector
        document.querySelectorAll('[data-sort-select]').forEach((sel) => {
            sel.addEventListener('change', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('sort', sel.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        });

        // Grid / List switcher
        const grid = document.querySelector('[data-catalog-grid]');
        const viewBtns = document.querySelectorAll('[data-view-mode]');
        if (grid && viewBtns.length) {
            const saved = localStorage.getItem('pm_catalog_view') || 'grid';
            const applyMode = (mode) => {
                grid.classList.toggle('is-list', mode === 'list');
                viewBtns.forEach((b) => b.classList.toggle('is-active', b.getAttribute('data-view-mode') === mode));
                localStorage.setItem('pm_catalog_view', mode);
            };
            applyMode(saved);
            viewBtns.forEach((btn) => {
                btn.addEventListener('click', () => applyMode(btn.getAttribute('data-view-mode') || 'grid'));
            });
        }

        // FAQ Instant Filter on faq.php
        const faqSearch = document.getElementById('pmFaqSearch');
        if (faqSearch) {
            faqSearch.addEventListener('input', () => {
                const q = faqSearch.value.trim().toLowerCase();
                document.querySelectorAll('[data-faq-searchable]').forEach((item) => {
                    const text = (item.textContent || '').toLowerCase();
                    item.style.display = q === '' || text.includes(q) ? '' : 'none';
                });
            });
        }
    });
})();
