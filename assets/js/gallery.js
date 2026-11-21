/* ============================================================================
   Project Screenshot Gallery, Lightbox Viewer & Tab Controller
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // Tabs
        document.querySelectorAll('[data-tabs]').forEach((group) => {
            const btns = group.querySelectorAll('[data-tab-target]');
            btns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-tab-target');
                    btns.forEach((b) => {
                        const active = b === btn;
                        b.classList.toggle('is-active', active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    document.querySelectorAll('[data-tab-panel]').forEach((panel) => {
                        panel.classList.toggle('is-active', panel.id === targetId);
                    });
                });
            });
        });

        // Gallery + Lightbox
        const stageImg = document.getElementById('pmGalleryMain');
        const thumbs = Array.from(document.querySelectorAll('[data-gallery-src]'));
        const lbImg = document.getElementById('pmLightboxImage');
        const lbCaption = document.getElementById('pmLightboxCaption');
        let currentIndex = 0;

        function selectSlide(idx) {
            if (!thumbs.length || !stageImg) return;
            currentIndex = (idx + thumbs.length) % thumbs.length;
            const thumb = thumbs[currentIndex];
            const src = thumb.getAttribute('data-gallery-src') || '';
            const caption = thumb.getAttribute('data-gallery-caption') || '';
            stageImg.src = src;
            stageImg.alt = caption;
            thumbs.forEach((t, i) => t.classList.toggle('is-active', i === currentIndex));
            if (lbImg) lbImg.src = src;
            if (lbCaption) lbCaption.textContent = caption + ' (' + (currentIndex + 1) + ' / ' + thumbs.length + ')';
        }

        thumbs.forEach((thumb, idx) => {
            thumb.addEventListener('click', () => selectSlide(idx));
        });

        const openLightbox = () => {
            if (!document.getElementById('pmLightboxModal')) return;
            selectSlide(currentIndex);
            window.PM.openModal('pmLightboxModal');
        };

        stageImg?.addEventListener('click', openLightbox);
        document.getElementById('pmGalleryZoomBtn')?.addEventListener('click', openLightbox);
        document.getElementById('pmLightboxPrev')?.addEventListener('click', () => selectSlide(currentIndex - 1));
        document.getElementById('pmLightboxNext')?.addEventListener('click', () => selectSlide(currentIndex + 1));

        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('pmLightboxModal');
            if (!modal || !modal.classList.contains('is-open')) return;
            if (e.key === 'ArrowLeft') selectSlide(currentIndex - 1);
            if (e.key === 'ArrowRight') selectSlide(currentIndex + 1);
        });
    });
})();
