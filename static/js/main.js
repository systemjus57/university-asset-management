/**
 * Shared UI behaviour: sidebar toggle, user dropdown, modals,
 * destructive-action confirmations, client-side table search/sort.
 * Loaded on every authenticated page.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ---- Sidebar toggle (tablet/mobile) ----
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // ---- User dropdown ----
    var userMenuBtn = document.getElementById('userMenuBtn');
    var userMenu = document.getElementById('userMenu');
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userMenu.classList.remove('open');
        });
    }

    // ---- Generic modal open/close ----
    document.querySelectorAll('[data-modal-target]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var modal = document.getElementById(trigger.getAttribute('data-modal-target'));
            if (modal) modal.classList.add('open');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var overlay = btn.closest('.modal-overlay');
            if (overlay) overlay.classList.remove('open');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // ---- Destructive action confirmation ----
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var message = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ---- Client-side table search ----
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        var tableId = input.getAttribute('data-table-search');
        var table = document.getElementById(tableId);
        if (!table) return;
        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
            });
        });
    });

    // ---- Client-side table column sort ----
    document.querySelectorAll('table').forEach(function (table) {
        var headers = table.querySelectorAll('thead th[data-sort]');
        headers.forEach(function (th, index) {
            th.addEventListener('click', function () {
                var tbody = table.querySelector('tbody');
                var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                var asc = th.getAttribute('data-sort-dir') !== 'asc';
                headers.forEach(function (h) { h.removeAttribute('data-sort-dir'); });
                th.setAttribute('data-sort-dir', asc ? 'asc' : 'desc');

                var colIndex = Array.prototype.indexOf.call(th.parentNode.children, th);
                rows.sort(function (a, b) {
                    var aText = a.children[colIndex] ? a.children[colIndex].textContent.trim() : '';
                    var bText = b.children[colIndex] ? b.children[colIndex].textContent.trim() : '';
                    var aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
                    var bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
                    var cmp;
                    if (!isNaN(aNum) && !isNaN(bNum) && aText.match(/[0-9]/)) {
                        cmp = aNum - bNum;
                    } else {
                        cmp = aText.localeCompare(bText);
                    }
                    return asc ? cmp : -cmp;
                });
                rows.forEach(function (row) { tbody.appendChild(row); });
            });
        });
    });

    // ---- Tabs ----
    document.querySelectorAll('.tab-link').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var group = tab.closest('.tabs');
            var panelId = tab.getAttribute('data-tab');
            group.querySelectorAll('.tab-link').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var panelsContainer = group.parentNode;
            panelsContainer.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
            var panel = document.getElementById(panelId);
            if (panel) panel.classList.add('active');
        });
    });
});
