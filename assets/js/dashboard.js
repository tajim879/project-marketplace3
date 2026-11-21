/* ============================================================================
   Buyer Dashboard Helpers (License Viewer, Review Modal & Report Modal)
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('click', (e) => {
        // Open License Certificate modal
        const licBtn = e.target.closest('[data-license-modal]');
        if (licBtn) {
            const title = licBtn.getAttribute('data-project-title') || '';
            const key = licBtn.getAttribute('data-license-key') || '';
            const type = licBtn.getAttribute('data-license-type') || '';
            const order = licBtn.getAttribute('data-order-number') || '';
            const date = licBtn.getAttribute('data-purchase-date') || '';

            const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            setText('pmLicProject', title);
            setText('pmLicKey', key);
            setText('pmLicType', type);
            setText('pmLicOrder', order);
            setText('pmLicDate', date);
            const copyBtn = document.getElementById('pmLicCopyBtn');
            if (copyBtn) copyBtn.setAttribute('data-copy', key);

            window.PM.openModal('pmLicenseModal');
            return;
        }

        // Open Review Report modal on Project Details
        const reportBtn = e.target.closest('[data-report-review]');
        if (reportBtn) {
            const rid = reportBtn.getAttribute('data-report-review') || '';
            const inp = document.getElementById('pmReportReviewId');
            if (inp) inp.value = rid;
            window.PM.openModal('pmReportReviewModal');
        }
    });
})();
