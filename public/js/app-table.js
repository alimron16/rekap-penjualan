// Global Table Helpers (Live Filter, Per-Page Selector, and Clean Offline Excel Export)

window.filterTable = function(tableId, query) {
    const q = (query || '').toLowerCase().trim();
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;

    rows.forEach(row => {
        if (row.classList.contains('empty-row')) return;
        const text = row.innerText.toLowerCase();
        if (!q || text.includes(q)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countEl = document.getElementById(tableId + '_count');
    if (countEl) {
        countEl.innerText = visibleCount + ' data';
    }
};

window.changeTablePerPage = function(select) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', select.value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
};

window.exportTableToExcel = function(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Tabel tidak ditemukan!');
        return;
    }

    const clone = table.cloneNode(true);

    // Remove elements that shouldn't be in the Excel export
    const toRemove = clone.querySelectorAll('.no-export, button, form, svg');
    toRemove.forEach(el => el.remove());

    // Remove action column (AKSI)
    const ths = clone.querySelectorAll('th');
    ths.forEach((th, idx) => {
        if (th.innerText.trim().toUpperCase() === 'AKSI') {
            const colIdx = idx;
            clone.querySelectorAll('tr').forEach(tr => {
                const cells = tr.children;
                if (cells[colIdx]) cells[colIdx].remove();
            });
        }
    });

    const today = new Date().toISOString().substring(0, 10);
    const safeName = (filename || 'Data_Export') + '_' + today;

    // Clean inline styling for Excel
    const style = `
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 10pt; }
            th { background-color: #133e1c; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 8px; text-align: center; }
            td { border: 1px solid #cccccc; padding: 6px; }
        </style>
    `;
    
    // UTF-8 BOM + styling + table HTML
    const excelContent = '\uFEFF' + style + clone.outerHTML;
    const blob = new Blob([excelContent], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = safeName + '.xls';
    document.body.appendChild(link);
    link.click();
    
    setTimeout(() => {
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }, 150);
};
