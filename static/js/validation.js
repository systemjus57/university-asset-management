/**
 * Lightweight client-side validation. This is a UX convenience layer only —
 * every form is re-validated server-side in PHP before touching the database.
 */
(function () {
    function showError(input, message) {
        clearError(input);
        input.classList.add('invalid');
        var span = document.createElement('div');
        span.className = 'field-error';
        span.textContent = message;
        span.setAttribute('data-generated-error', '1');
        input.insertAdjacentElement('afterend', span);
    }

    function clearError(input) {
        input.classList.remove('invalid');
        var next = input.nextElementSibling;
        if (next && next.hasAttribute('data-generated-error')) {
            next.remove();
        }
    }

    function validateField(input) {
        clearError(input);

        if (input.hasAttribute('required') && !input.value.trim()) {
            showError(input, 'This field is required.');
            return false;
        }
        if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
            showError(input, 'Enter a valid email address.');
            return false;
        }
        if (input.type === 'number' && input.value !== '') {
            var val = parseFloat(input.value);
            var min = input.hasAttribute('min') ? parseFloat(input.min) : null;
            var max = input.hasAttribute('max') ? parseFloat(input.max) : null;
            if (min !== null && val < min) { showError(input, 'Value must be at least ' + min + '.'); return false; }
            if (max !== null && val > max) { showError(input, 'Value must be at most ' + max + '.'); return false; }
        }
        if (input.hasAttribute('minlength') && input.value && input.value.length < parseInt(input.getAttribute('minlength'), 10)) {
            showError(input, 'Must be at least ' + input.getAttribute('minlength') + ' characters.');
            return false;
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var fields = form.querySelectorAll('input[required], input[type=email], input[type=number], textarea[required], select[required]');
                var valid = true;
                fields.forEach(function (field) {
                    if (!validateField(field)) valid = false;
                });
                if (!valid) {
                    e.preventDefault();
                    var firstError = form.querySelector('.invalid');
                    if (firstError) firstError.focus();
                }
            });

            form.querySelectorAll('input, textarea, select').forEach(function (field) {
                field.addEventListener('blur', function () { validateField(field); });
            });
        });
    });
})();
