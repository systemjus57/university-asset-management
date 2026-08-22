/**
 * Filters the Custodian dropdown on modules/assigned/add.php down to users
 * in the currently selected department. Purely a UX convenience — the
 * server re-validates that the submitted custodian actually belongs to the
 * submitted department regardless of what this script does.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var deptSelect = document.querySelector('[data-department-select]');
        var userSelect = document.querySelector('[data-custodian-select]');
        if (!deptSelect || !userSelect) return;

        var options = Array.prototype.slice.call(userSelect.options);

        function applyFilter() {
            var deptId = deptSelect.value;
            var selectedStillValid = false;

            options.forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false;
                    return;
                }
                var matches = !deptId || opt.getAttribute('data-department') === deptId;
                opt.hidden = !matches;
                if (matches && opt.selected) selectedStillValid = true;
            });

            if (!selectedStillValid && userSelect.value !== '') {
                userSelect.value = '';
            }
        }

        deptSelect.addEventListener('change', applyFilter);
        applyFilter();
    });
})();
