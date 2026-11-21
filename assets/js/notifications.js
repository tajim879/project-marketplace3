/* ============================================================================
   Notifications AJAX Actions (Mark Read, Mark All Read, Delete)
   ============================================================================ */

(function () {
    'use strict';

    document.addEventListener('click', async (e) => {
        const markAllBtn = e.target.closest('[data-notif-mark-all]');
        if (markAllBtn) {
            e.preventDefault();
            const res = await window.PM.post('actions/notifications/manage.php', { action: 'mark_all_read' });
            if (res.success) {
                document.querySelectorAll('.pm-notif-item.is-unread, [data-notif-row].is-unread').forEach((el) => {
                    el.classList.remove('is-unread');
                });
                window.PM.updateCounter('[data-notif-count]', 0);
                window.PM.toast(res.message, 'success');
            }
            return;
        }

        const markOneBtn = e.target.closest('[data-notif-read]');
        if (markOneBtn) {
            e.preventDefault();
            const id = markOneBtn.getAttribute('data-notif-read');
            const res = await window.PM.post('actions/notifications/manage.php', { action: 'mark_read', id });
            if (res.success) {
                markOneBtn.closest('[data-notif-row]')?.classList.remove('is-unread');
                markOneBtn.remove();
                window.PM.updateCounter('[data-notif-count]', res.unread_count);
                window.PM.toast('Marked as read', 'success');
            }
            return;
        }

        const delBtn = e.target.closest('[data-notif-delete]');
        if (delBtn) {
            e.preventDefault();
            const id = delBtn.getAttribute('data-notif-delete');
            const res = await window.PM.post('actions/notifications/manage.php', { action: 'delete', id });
            if (res.success) {
                delBtn.closest('[data-notif-row]')?.remove();
                window.PM.updateCounter('[data-notif-count]', res.unread_count);
                window.PM.toast('Notification deleted', 'success');
            }
        }
    });
})();
