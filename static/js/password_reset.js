/**
 * Login page "Forgot Password?" panel switching. Loaded only by
 * modules/auth/login.php, alongside validation.js.
 *
 * Reuses the existing .tab-panel / .tab-panel.active convention already
 * defined in static/css/style.css (see modules/profile/index.php).
 *
 * This script only toggles which already-rendered panel is visible for the
 * cosmetic "Forgot Password? -> enter email" step, before anything has been
 * submitted to the server. Every step that actually sends data (email, OTP,
 * new password) is a real form POST handled server-side in login.php —
 * disabling JavaScript does not bypass any validation or security check,
 * it only means that first panel switch has to happen via a full page
 * response instead of instantly.
 */
(function () {
    function showPanel(id) {
        document.querySelectorAll('.auth-card .tab-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === id);
        });
        var firstField = document.querySelector('#' + id + ' input:not([type=hidden])');
        if (firstField) firstField.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-auth-show]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showPanel(btn.getAttribute('data-auth-show'));
            });
        });

        var cancelForm = document.getElementById('cancelResetForm');
        document.querySelectorAll('[data-auth-cancel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (cancelForm) cancelForm.requestSubmit();
            });
        });

        // If the server rendered a non-login panel active (e.g. after submitting an email or code),
        // focus its first field so keyboard/typing users can continue immediately.
        var active = document.querySelector('.auth-card .tab-panel.active');
        if (active && active.id !== 'panel-login') {
            var firstField = active.querySelector('input:not([type=hidden])');
            if (firstField) firstField.focus();
        }
    });
})();
