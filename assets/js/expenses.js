// expenses.js

let categoryDonutChart = null;
let forecastBarChart = null;
let currentPage = 1;
let itemsPerPage = 10;
let allExpenses = [];
let filteredExpenses = [];

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    fetchCategories();
    fetchMetrics();
    fetchExpenses();
    fetchChartData();

    // Filter listeners
    document.getElementById('filter-category').addEventListener('change', () => { currentPage = 1; applyFilters(); });
    document.getElementById('filter-status').addEventListener('change', () => { currentPage = 1; applyFilters(); });
    document.getElementById('filter-date').addEventListener('change', () => { currentPage = 1; applyFilters(); });

    document.getElementById('clear-filters-btn').addEventListener('click', () => {
        document.getElementById('filter-category').value = 'all';
        document.getElementById('filter-status').value = 'all';
        document.getElementById('filter-date').value = '';
        document.getElementById('table-search').value = '';
        currentPage = 1;
        fetchExpenses();
    });

    // Rows per page
    document.getElementById('rows-per-page').addEventListener('change', (e) => {
        itemsPerPage = parseInt(e.target.value);
        currentPage = 1;
        renderPage();
    });

    // Search
    let searchTimeout;
    document.getElementById('table-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; applyFilters(); }, 300);
    });

    // Modal
    const modal = document.getElementById('expenseModal');
    document.getElementById('add-expense-btn').onclick = () => openModal();
    document.querySelector('.close-modal').onclick = () => closeModal();
    document.querySelector('.cancel-modal').onclick = () => closeModal();
    window.onclick = (event) => { if (event.target == modal) closeModal(); };
    document.getElementById('expenseForm').addEventListener('submit', handleFormSubmit);
});

// ─── Filtering & Search ──────────────────────────────────────────────────────

function applyFilters() {
    const search  = document.getElementById('table-search').value.toLowerCase().trim();
    const cat     = document.getElementById('filter-category').value;
    const stat    = document.getElementById('filter-status').value;
    const date    = document.getElementById('filter-date').value;

    filteredExpenses = allExpenses.filter(item => {
        const matchSearch = !search ||
            (item.vendor_item_name || '').toLowerCase().includes(search) ||
            (item.category_name   || '').toLowerCase().includes(search) ||
            (item.notes           || '').toLowerCase().includes(search);

        const matchCat  = cat  === 'all' || String(item.category_id) === String(cat);
        const matchStat = stat === 'all' || item.payment_status === stat;
        const matchDate = !date || item.date_logged === date;

        return matchSearch && matchCat && matchStat && matchDate;
    });

    renderPage();
}

// ─── Fetch from API ──────────────────────────────────────────────────────────

async function fetchExpenses() {
    const tbody = document.getElementById('expenses-table-body');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading...</td></tr>';

    try {
        const response = await fetch('/smart-planner/api/expenses.php?action=list');
        const result   = await response.json();

        if (result.status === 'success') {
            allExpenses      = result.data || [];
            filteredExpenses = [...allExpenses];
            renderPage();
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:red;">${result.message}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:red;">Failed to load expenses.</td></tr>';
    }
}

async function fetchCategories() {
    try {
        const result = await (await fetch('/smart-planner/api/expenses.php?action=categories')).json();
        if (result.status === 'success') {
            const sel  = document.getElementById('filter-category');
            const form = document.getElementById('category_id');
            sel.innerHTML  = '<option value="all">All Categories</option>';
            form.innerHTML = '<option value="">Select Category</option>';
            result.data.forEach(cat => {
                sel.add(new Option(cat.name, cat.id));
                form.add(new Option(cat.name, cat.id));
            });
        }
    } catch(err) { console.error(err); }
}

async function fetchMetrics() {
    try {
        const result = await (await fetch('/smart-planner/api/expenses.php?action=metrics')).json();
        if (result.status === 'success') {
            const d = result.data;
            const fmt = (v) => parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('total-expenses-amount').innerText   = fmt(d.total_expenses);
            document.getElementById('pending-approval-amount').innerText = fmt(d.pending_amount);
            document.getElementById('pending-approval-count').innerText  = d.pending_count;
            document.getElementById('remaining-budget-amount').innerText = fmt(d.remaining_budget);
            document.getElementById('remaining-budget-progress').style.width = d.budget_percent + '%';
            document.getElementById('total-expenses-trend').innerText = 'Updated realtime';
        }
    } catch(err) { console.error(err); }
}

// ─── Render Table (Paginated) ────────────────────────────────────────────────

function renderPage() {
    const total  = filteredExpenses.length;
    const start  = (currentPage - 1) * itemsPerPage;
    const end    = Math.min(start + itemsPerPage, total);
    const pageData = filteredExpenses.slice(start, end);

    const tbody = document.getElementById('expenses-table-body');
    tbody.innerHTML = '';

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#9ca3af;">No expenses found.</td></tr>';
    } else {
        pageData.forEach(item => {
            const tr = document.createElement('tr');
            const initials = item.vendor_item_name ? item.vendor_item_name.substring(0, 2).toUpperCase() : '??';
            let iconBg = '#e0e7ff', iconCol = '#4338ca';
            if (item.payment_status === 'Overdue') { iconBg = '#fee2e2'; iconCol = '#b91c1c'; }
            if (item.payment_status === 'Pending') { iconBg = '#fef3c7'; iconCol = '#b45309'; }

            const dateStr = new Date(item.date_logged).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const fmt = (v) => parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2});

            tr.innerHTML = `
                <td data-label="Vendor">
                    <div class="vendor-cell">
                        <div class="vendor-icon" style="background-color:${iconBg};color:${iconCol};">${initials}</div>
                        <div class="vendor-details">
                            <strong>${item.vendor_item_name}</strong>
                            <span>${item.notes || ''}</span>
                        </div>
                    </div>
                </td>
                <td data-label="Category"><span class="category-badge">${item.category_name || 'Uncategorized'}</span></td>
                <td data-label="Estimated Cost">${fmt(item.estimated_cost)}</td>
                <td data-label="Actual Cost"><strong>${fmt(item.actual_cost)}</strong></td>
                <td data-label="Status">
                    <span class="status-${item.payment_status.replace(' ', '.')}">
                        <span class="status-dot"></span>${item.payment_status}
                    </span>
                </td>
                <td data-label="Date">${dateStr}</td>
                <td>
                    <div style="display:flex; gap:0.5rem;">
                        <button onclick="openModal(${item.id})" title="Edit" style="background:none; border:none; cursor:pointer; color:#6366f1; padding:4px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </button>
                        <button onclick="deleteExpense(${item.id})" title="Delete" style="background:none; border:none; cursor:pointer; color:#ef4444; padding:4px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    updatePagination(total, start, end);
}

// ─── Pagination ──────────────────────────────────────────────────────────────

function updatePagination(total, start, end) {
    const totalPages = Math.max(1, Math.ceil(total / itemsPerPage));

    if (total === 0) {
        document.getElementById('pagination-info').innerText = 'No entries found';
    } else {
        document.getElementById('pagination-info').innerText = `Showing ${start + 1} to ${end} of ${total} entries`;
    }

    const controls = document.getElementById('pagination-controls');
    controls.innerHTML = '';

    // Prev button (icon)
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn page-icon-btn' + (currentPage === 1 ? ' disabled' : '');
    prevBtn.disabled  = currentPage === 1;
    prevBtn.title     = 'Previous page';
    prevBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>`;
    prevBtn.onclick   = () => { if (currentPage > 1) { currentPage--; renderPage(); } };
    controls.appendChild(prevBtn);

    // Page number buttons
    let startPage = Math.max(1, currentPage - 2);
    let endPage   = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    if (startPage > 1) {
        const firstBtn = document.createElement('button');
        firstBtn.className = 'page-btn';
        firstBtn.innerText = '1';
        firstBtn.onclick   = () => { currentPage = 1; renderPage(); };
        controls.appendChild(firstBtn);
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.innerText = '…';
            controls.appendChild(ellipsis);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
        btn.innerText = i;
        btn.onclick   = ((page) => () => { currentPage = page; renderPage(); })(i);
        controls.appendChild(btn);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.innerText = '…';
            controls.appendChild(ellipsis);
        }
        const lastBtn = document.createElement('button');
        lastBtn.className = 'page-btn';
        lastBtn.innerText = totalPages;
        lastBtn.onclick   = () => { currentPage = totalPages; renderPage(); };
        controls.appendChild(lastBtn);
    }

    // Next button (icon)
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn page-icon-btn' + (currentPage === totalPages ? ' disabled' : '');
    nextBtn.disabled  = currentPage === totalPages;
    nextBtn.title     = 'Next page';
    nextBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>`;
    nextBtn.onclick   = () => { if (currentPage < totalPages) { currentPage++; renderPage(); } };
    controls.appendChild(nextBtn);
}

// ─── Modal ───────────────────────────────────────────────────────────────────

function openModal(id = null) {
    const modal = document.getElementById('expenseModal');
    const form  = document.getElementById('expenseForm');

    if (id) {
        const expense = allExpenses.find(e => e.id == id);
        if (expense) {
            document.getElementById('modal-title').innerText       = 'Edit Expense';
            document.getElementById('expense_id').value            = expense.id;
            document.getElementById('vendor_item_name').value      = expense.vendor_item_name;
            document.getElementById('category_id').value           = expense.category_id;
            document.getElementById('date_logged').value           = expense.date_logged;
            document.getElementById('estimated_cost').value        = expense.estimated_cost;
            document.getElementById('actual_cost').value           = expense.actual_cost;
            document.getElementById('payment_status').value        = expense.payment_status;
            document.getElementById('notes').value                 = expense.notes || '';
        }
    } else {
        document.getElementById('modal-title').innerText = 'Add Expense';
        form.reset();
        document.getElementById('expense_id').value  = '';
        document.getElementById('date_logged').value = new Date().toISOString().split('T')[0];
    }
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('expenseModal').classList.remove('active');
    document.getElementById('expenseForm').reset();
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    try {
        const result = await (await fetch('/smart-planner/api/expenses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })).json();

        if (result.status === 'success') {
            closeModal();
            await fetchExpenses();
            applyFilters();
            fetchMetrics();
            fetchChartData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch(err) {
        console.error(err);
        alert('An error occurred while saving.');
    }
}

// ─── Dropdown Actions ────────────────────────────────────────────────────────

window.toggleDropdown = function(id) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== `action-menu-${id}`) menu.classList.remove('show');
    });
    document.getElementById(`action-menu-${id}`).classList.toggle('show');
};

document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    }
});

window.deleteExpense = async function(id) {
    if (!confirm('Are you sure you want to delete this expense?')) return;
    try {
        const result = await (await fetch(`/smart-planner/api/expenses.php?action=delete&id=${id}`, { method: 'DELETE' })).json();
        if (result.status === 'success') {
            await fetchExpenses();
            applyFilters();
            fetchMetrics();
            fetchChartData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch(err) { console.error(err); }
};

// ─── Charts ──────────────────────────────────────────────────────────────────

const chartColors = ['#4f46e5', '#8b5cf6', '#0d9488', '#ef4444', '#f59e0b', '#3b82f6'];

function initCharts() {
    if (typeof Chart === 'undefined') return;

    const donutCtx = document.getElementById('categoryDonutChart').getContext('2d');
    categoryDonutChart = new Chart(donutCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: chartColors, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
    });

    const barCtx = document.getElementById('forecastBarChart').getContext('2d');
    forecastBarChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                { label: 'Actual Spend',    data: [], backgroundColor: '#4f46e5', borderRadius: 4 },
                { label: 'Projected Spend', data: [], backgroundColor: 'rgba(79,70,229,0.2)', borderColor: '#4f46e5', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { display: false, beginAtZero: true }, x: { grid: { display: false } } },
            plugins: { legend: { display: false } }
        }
    });
}

async function fetchChartData() {
    try {
        const result = await (await fetch('/smart-planner/api/expenses.php?action=charts')).json();
        if (result.status === 'success' && categoryDonutChart && forecastBarChart) {
            const d = result.data;

            // Donut
            const legendContainer = document.getElementById('donut-legend');
            legendContainer.innerHTML = '';
            if (d.donut && d.donut.length > 0) {
                const total = d.donut.reduce((s, i) => s + parseFloat(i.total), 0);
                const labels = [], data = [];
                d.donut.forEach((item, i) => {
                    const color   = chartColors[i % chartColors.length];
                    const catName = item.category_name || 'Uncategorized';
                    const pct     = total > 0 ? Math.round((parseFloat(item.total) / total) * 100) : 0;
                    labels.push(catName);
                    data.push(parseFloat(item.total));
                    legendContainer.innerHTML += `<div class="legend-item"><div class="legend-color" style="background-color:${color};"></div><span>${catName} - ${pct}%</span></div>`;
                });
                categoryDonutChart.data.labels = labels;
                categoryDonutChart.data.datasets[0].data = data;
            } else {
                legendContainer.innerHTML = '<p style="font-size:0.875rem;color:#9ca3af;">No data available.</p>';
                categoryDonutChart.data.labels = [];
                categoryDonutChart.data.datasets[0].data = [];
            }
            categoryDonutChart.update();

            // Bar
            if (d.bar && d.bar.length > 0) {
                forecastBarChart.data.labels                   = d.bar.map(i => i.month);
                forecastBarChart.data.datasets[0].data         = d.bar.map(i => parseFloat(i.actual));
                forecastBarChart.data.datasets[1].data         = d.bar.map(i => parseFloat(i.projected));
            } else {
                forecastBarChart.data.labels = [];
                forecastBarChart.data.datasets[0].data = [];
                forecastBarChart.data.datasets[1].data = [];
            }
            forecastBarChart.update();
        }
    } catch(err) { console.error(err); }
}
