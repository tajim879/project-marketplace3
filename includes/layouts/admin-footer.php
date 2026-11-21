    </main>
</div>

<div id="pmToastStack" class="pm-toast-stack" aria-live="polite"></div>

<script src="<?= e(asset_url('js/ajax.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/validation.js')) ?>" defer></script>
<script>
(function () {
    'use strict';
    var btn = document.getElementById('pmAdminMenuBtn');
    var closeBtn = document.getElementById('pmAdminCloseBtn');
    var sb = document.getElementById('pmAdminSidebar');
    var bd = document.getElementById('pmAdminBackdrop');
    if (!btn || !sb || !bd) return;
    function close() {
        sb.classList.remove('is-open');
        bd.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
    btn.addEventListener('click', function () {
        var open = sb.classList.toggle('is-open');
        bd.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    });
    if (closeBtn) closeBtn.addEventListener('click', close);
    bd.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
</script>
</body>
</html>
<?php clear_old_input(); ?>
