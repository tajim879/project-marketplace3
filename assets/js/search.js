/* ============================================================================
   Live Instant Search Modal (Ctrl/Cmd + K & Header Search Button)
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('pmQuickSearchInput');
        const resultsBox = document.getElementById('pmQuickSearchResults');
        if (!input || !resultsBox) return;

        let timer = null;

        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                window.PM.openModal('pmSearchModal');
            }
        });

        function renderSkeleton() {
            resultsBox.innerHTML = Array.from({ length: 3 }).map(() =>
                '<div class="pm-live-item"><div class="pm-skeleton" style="width:56px;height:40px;border-radius:8px;"></div><div style="flex:1;display:grid;gap:6px;"><div class="pm-skeleton pm-skeleton-line" style="width:65%;"></div><div class="pm-skeleton pm-skeleton-line" style="width:40%;height:11px;"></div></div></div>'
            ).join('');
        }

        function esc(str) {
            const d = document.createElement('div');
            d.textContent = str || '';
            return d.innerHTML;
        }

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) {
                resultsBox.innerHTML = '<p class="pm-muted" style="text-align:center;padding:18px 8px;">Type at least 2 characters to search PHP, Android, Kotlin, WordPress, Python &amp; UI/UX projects.</p>';
                return;
            }
            renderSkeleton();
            timer = setTimeout(async () => {
                try {
                    const data = await window.PM.get('actions/projects/live-search.php', { q });
                    const items = data.items || [];
                    if (!items.length) {
                        resultsBox.innerHTML = '<p class="pm-muted" style="text-align:center;padding:22px 8px;">No matching projects for "' + esc(q) + '". Press Enter to run a full search.</p>';
                        return;
                    }
                    resultsBox.innerHTML = items.map((item) =>
                        '<a class="pm-live-item" href="' + esc(item.url) + '">'
                        + '<img src="' + esc(item.thumbnail) + '" alt="" loading="lazy">'
                        + '<span class="pm-live-item-info"><strong>' + esc(item.title) + '</strong><small>' + esc(item.category) + ' · by ' + esc(item.seller) + '</small></span>'
                        + '<span class="pm-price-current ' + (item.is_free ? 'is-free' : '') + '" style="font-size:0.92rem;">' + esc(item.price_label) + '</span>'
                        + '</a>'
                    ).join('');
                } catch {
                    resultsBox.innerHTML = '<p class="pm-muted" style="text-align:center;padding:18px;">Unable to load live suggestions.</p>';
                }
            }, 200);
        });
    });
})();
