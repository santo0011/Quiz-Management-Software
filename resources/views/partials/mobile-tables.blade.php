<script>
    (() => {
        const mobileQuery = window.matchMedia('(max-width: 575.98px)');

        const cellLabel = (table, index) => {
            const header = table.querySelectorAll('thead th')[index];
            return header?.textContent?.trim() || 'Detail';
        };

        const enhanceTable = (table, tableIndex) => {
            if (table.dataset.mobileEnhanced === 'true') {
                return;
            }

            const directDetails = table.hasAttribute('data-mobile-direct-details');
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.children.length > 1);

            rows.forEach((row, rowIndex) => {
                const detailId = `mobile-table-detail-${tableIndex}-${rowIndex}`;
                const cells = Array.from(row.children);
                const statusIndex = cells.findIndex((cell, index) => index > 0 && cell.querySelector('.status-badge, .exam-status-badge'));
                const primaryIndex = 0;
                const secondaryIndex = statusIndex > -1 ? statusIndex : Math.min(1, cells.length - 1);

                cells.forEach((cell, index) => {
                    cell.dataset.mobileLabel = cellLabel(table, index);
                    cell.classList.toggle('mobile-primary-cell', index === primaryIndex);
                    cell.classList.toggle('mobile-secondary-cell', index === secondaryIndex);
                    cell.classList.toggle('mobile-hidden-detail-source', index !== primaryIndex && index !== secondaryIndex);
                });

                const toggleCell = document.createElement('td');
                toggleCell.className = 'mobile-table-toggle-cell';
                const detailsLink = row.querySelector('td:last-child a[href]');

                if (directDetails && detailsLink) {
                    const link = detailsLink.cloneNode(true);
                    link.className = 'btn btn-sm btn-soft mobile-table-toggle';
                    link.innerHTML = '<i class="bi bi-eye-fill"></i><span>Details</span>';
                    toggleCell.appendChild(link);
                    row.appendChild(toggleCell);
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-soft mobile-table-toggle';
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-controls', detailId);
                button.innerHTML = '<i class="bi bi-chevron-down"></i><span>Details</span>';
                toggleCell.appendChild(button);
                row.appendChild(toggleCell);

                const detailRow = document.createElement('tr');
                detailRow.className = 'mobile-table-detail-row';
                detailRow.id = button.getAttribute('aria-controls');
                const detailCell = document.createElement('td');
                detailCell.colSpan = cells.length + 1;
                const detailPanel = document.createElement('div');
                detailPanel.className = 'mobile-table-detail-panel';

                cells.forEach((cell, index) => {
                    if (index === primaryIndex || index === secondaryIndex) {
                        return;
                    }

                    const item = document.createElement('div');
                    item.className = cell.classList.contains('text-end') || cell.querySelector('.action-group, form, .btn')
                        ? 'mobile-table-detail-item mobile-table-actions'
                        : 'mobile-table-detail-item';
                    const label = document.createElement('span');
                    label.textContent = cell.dataset.mobileLabel;
                    const value = document.createElement('div');
                    value.append(...Array.from(cell.childNodes).map((node) => node.cloneNode(true)));
                    item.append(label, value);
                    detailPanel.appendChild(item);
                });

                detailCell.appendChild(detailPanel);
                detailRow.appendChild(detailCell);
                row.after(detailRow);

                button.addEventListener('click', () => {
                    const isOpen = detailRow.classList.toggle('is-open');
                    button.setAttribute('aria-expanded', String(isOpen));
                    button.querySelector('i').className = isOpen ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
                });
            });

            table.dataset.mobileEnhanced = 'true';
        };

        const enhanceMobileTables = () => {
            if (! mobileQuery.matches) {
                return;
            }

            document.querySelectorAll('.admin-table').forEach(enhanceTable);
        };

        enhanceMobileTables();
        window.addEventListener('resize', enhanceMobileTables, { passive: true });
    })();
</script>
