/* ============================================================================
   Centralized Fetch / AJAX & SVG Toast Notification Engine
   ============================================================================ */

(function () {
    'use strict';

    const metaCsrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const baseUrl = () => document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

    const ICONS = {
        success: '<svg class="pm-icon pm-toast-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg class="pm-icon pm-toast-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        info: '<svg class="pm-icon pm-toast-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    function resolveUrl(path) {
        if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
        const b = baseUrl().replace(/\/$/, '');
        return b + '/' + path.replace(/^\//, '');
    }

    function toast(message, type = 'info') {
        let stack = document.getElementById('pmToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'pmToastStack';
            stack.className = 'pm-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        const el = document.createElement('div');
        const safeType = ICONS[type] ? type : 'info';
        el.className = 'pm-toast is-' + safeType;
        const span = document.createElement('span');
        span.textContent = message;
        el.innerHTML = ICONS[safeType];
        el.appendChild(span);
        stack.appendChild(el);

        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(6px)';
            el.style.transition = 'opacity 180ms ease, transform 180ms ease';
            setTimeout(() => el.remove(), 200);
        }, 3600);
    }

    async function post(endpoint, data = {}) {
        const body = new URLSearchParams();
        Object.entries(data).forEach(([k, v]) => {
            if (v !== undefined && v !== null) body.append(k, String(v));
        });
        body.append('_csrf_token', metaCsrf());

        const res = await fetch(resolveUrl(endpoint), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': metaCsrf()
            },
            body: body.toString()
        });

        const payload = await res.json().catch(() => ({ success: false, message: 'Unexpected server response.' }));
        if (res.status === 401 && payload.requires_auth && payload.login_url) {
            toast(payload.message || 'Please sign in first.', 'info');
            setTimeout(() => { window.location.href = payload.login_url; }, 650);
        }
        return payload;
    }

    async function get(endpoint, params = {}) {
        const qs = new URLSearchParams(params).toString();
        const full = resolveUrl(endpoint) + (qs ? '?' + qs : '');
        const res = await fetch(full, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return res.json();
    }

    function updateCounter(selector, count) {
        document.querySelectorAll(selector).forEach((el) => {
            const n = Math.max(0, Number(count) || 0);
            el.textContent = n > 99 ? '99+' : String(n);
            el.hidden = n === 0;
        });
    }

    window.PM = { post, get, toast, updateCounter, resolveUrl };
})();
