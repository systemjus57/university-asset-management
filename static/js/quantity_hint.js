/**
 * Shows how many units of the selected asset are available and caps the
 * Quantity field's max accordingly. Pure UX convenience — the server always
 * re-validates the submitted quantity against the real available count
 * regardless of what this script does client-side.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var assetSelect = document.querySelector('[data-asset-select]');
        var qtyInput     = document.querySelector('[data-quantity-input]');
        var hint         = document.querySelector('[data-available-hint]');
        if (!assetSelect || !qtyInput) return;

        function applyAvailable() {
            var opt = assetSelect.options[assetSelect.selectedIndex];
            var available = opt ? parseInt(opt.getAttribute('data-available'), 10) : NaN;
            if (!opt || opt.value === '' || isNaN(available)) {
                qtyInput.removeAttribute('max');
                if (hint) hint.textContent = 'Select an asset to see how many units are available.';
                return;
            }
            qtyInput.setAttribute('max', available);
            if (hint) hint.textContent = available + ' unit(s) currently available.';
        }

        assetSelect.addEventListener('change', applyAvailable);
        applyAvailable();
    });
})();
