(function () {
    'use strict';

    var root = document.documentElement;
    var sidebarButton = document.getElementById('sidebar-toggle');

    if (sidebarButton) {
        function updateSidebarButton() {
            var collapsed = root.classList.contains('sidebar-is-collapsed');
            sidebarButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarButton.setAttribute('aria-label', collapsed ? 'Развернуть меню' : 'Свернуть меню');
        }

        sidebarButton.addEventListener('click', function () {
            root.classList.toggle('sidebar-is-collapsed');
            try {
                localStorage.setItem('bank-sidebar-collapsed', root.classList.contains('sidebar-is-collapsed') ? '1' : '0');
            } catch (error) {}
            updateSidebarButton();
        });

        updateSidebarButton();
    }

    var collator = new Intl.Collator('ru', {
        numeric: true,
        sensitivity: 'base'
    });

    document.querySelectorAll('[data-enhanced-table]').forEach(function (container) {
        var searchInput = container.querySelector('[data-table-search]');
        var tbody = container.querySelector('tbody');
        var emptyMessage = container.querySelector('[data-table-empty]');
        var sortButtons = Array.prototype.slice.call(container.querySelectorAll('[data-sort-key]'));

        if (!tbody) return;

        var rows = Array.prototype.slice.call(tbody.querySelectorAll('[data-table-row]'));
        var sortKey = '';
        var sortDirection = 'asc';

        function cellValue(row, key, type) {
            var cell = row.querySelector('[data-column="' + key + '"]');
            var value = cell ? (cell.getAttribute('data-sort-value') || cell.textContent || '') : '';
            value = value.trim();

            if (type === 'number') {
                var number = Number(value.replace(/\s/g, '').replace(',', '.'));
                return Number.isNaN(number) ? 0 : number;
            }

            return value;
        }

        function filterRows() {
            var query = searchInput ? searchInput.value.trim().toLocaleLowerCase('ru') : '';
            var visibleCount = 0;

            rows.forEach(function (row) {
                var matches = query === '' || row.textContent.toLocaleLowerCase('ru').includes(query);
                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });

            if (emptyMessage) emptyMessage.hidden = visibleCount !== 0;
        }

        sortButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var key = button.getAttribute('data-sort-key') || '';
                var type = button.getAttribute('data-sort-type') || 'text';

                sortDirection = sortKey === key && sortDirection === 'asc' ? 'desc' : 'asc';
                sortKey = key;

                rows.sort(function (leftRow, rightRow) {
                    var left = cellValue(leftRow, key, type);
                    var right = cellValue(rightRow, key, type);
                    var result = type === 'number'
                        ? left - right
                        : collator.compare(String(left), String(right));
                    return sortDirection === 'asc' ? result : -result;
                });

                rows.forEach(function (row) { tbody.appendChild(row); });

                sortButtons.forEach(function (otherButton) {
                    var th = otherButton.closest('th');
                    otherButton.classList.remove('sort-active');
                    otherButton.querySelector('.sort-indicator').textContent = '↕';
                    if (th) th.setAttribute('aria-sort', 'none');
                });

                button.classList.add('sort-active');
                button.querySelector('.sort-indicator').textContent = sortDirection === 'asc' ? '↑' : '↓';
                var activeHeader = button.closest('th');
                if (activeHeader) activeHeader.setAttribute('aria-sort', sortDirection === 'asc' ? 'ascending' : 'descending');
                filterRows();
            });
        });

        if (searchInput) searchInput.addEventListener('input', filterRows);
    });

    var projectButton = document.getElementById('project-info-button');
    var projectModal = document.getElementById('project-modal');
    var projectDialog = projectModal ? projectModal.querySelector('.project-modal-dialog') : null;

    if (projectButton && projectModal && projectDialog) {
        function closeProjectModal() {
            projectModal.hidden = true;
            document.body.classList.remove('modal-open');
            projectButton.focus();
        }

        projectButton.addEventListener('click', function () {
            projectModal.hidden = false;
            document.body.classList.add('modal-open');
            projectDialog.focus();
        });

        projectModal.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', closeProjectModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !projectModal.hidden) closeProjectModal();
        });
    }
}());
