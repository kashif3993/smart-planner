<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login');
    exit;
}

$page_css = 'expenses.css';
$page_js = 'expenses.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
?>

<main class="main-content expenses-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Expenses Management</h1>
            <p>Track and manage your event financial ecosystem in real time.</p>
        </div>
        <div class="header-actions">
            <button class="icon-btn" title="Share"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></button>
            <button class="icon-btn" title="Print" onclick="window.print()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg></button>
            <button class="btn btn-primary" id="add-expense-btn">Add Expense</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card summary-card border-purple">
            <div class="card-info">
                <span class="card-title">TOTAL EXPENSES</span>
                <h2 id="total-expenses-amount">$0.00</h2>
                <span class="trend positive"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> <span id="total-expenses-trend">0% vs last month</span></span>
            </div>
            <div class="card-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--bg-purple-light)" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg></div>
        </div>
        <div class="card summary-card border-teal">
            <div class="card-info">
                <span class="card-title">PENDING APPROVAL</span>
                <h2 id="pending-approval-amount">$0.00</h2>
                <span class="trend neutral"><span id="pending-approval-count">0</span> line items require review</span>
            </div>
            <div class="card-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--bg-teal-light)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg></div>
        </div>
        <div class="card summary-card border-teal-dark">
            <div class="card-info">
                <span class="card-title">REMAINING BUDGET</span>
                <h2 id="remaining-budget-amount">$0.00</h2>
                <div class="progress-bar-mini"><div class="fill" id="remaining-budget-progress" style="width: 0%;"></div></div>
            </div>
            <div class="card-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--bg-teal-light)" stroke-width="2"><line x1="12" y1="2" x2="12" y2="22"/><line x1="17" y1="5" x2="17" y2="22"/><line x1="7" y1="10" x2="7" y2="22"/><line x1="2" y1="15" x2="2" y2="22"/><line x1="22" y1="2" x2="22" y2="22"/></svg></div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="card table-card">
        <!-- Single combined toolbar -->
        <div class="datatable-toolbar">
            <div class="dt-left-group">
                <div class="dt-rows-select">
                    <label for="rows-per-page">Show</label>
                    <select id="rows-per-page" class="filter-select dt-rows-dropdown">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <select id="filter-category" class="filter-select">
                    <option value="all">All Categories</option>
                </select>
                <select id="filter-status" class="filter-select">
                    <option value="all">Payment Status</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending">Pending</option>
                    <option value="Partially Paid">Partially Paid</option>
                    <option value="Overdue">Overdue</option>
                </select>
                <input type="date" id="filter-date" class="filter-select">
            </div>
            <div class="dt-right-group">
                <div class="dt-search">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" id="table-search" class="dt-search-input" placeholder="Search expenses...">
                </div>
                <button class="btn btn-text text-purple" id="clear-filters-btn" title="Clear filters">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Estimated Cost</th>
                        <th>Actual Cost</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="expenses-table-body">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
        
        <div class="pagination-container">
            <div class="pagination-info" id="pagination-info">Showing 0 to 0 of 0 entries</div>
            <div class="pagination-controls" id="pagination-controls">
                <!-- Loaded via JS -->
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="card chart-card">
            <div class="card-header">
                <h3>Spending by Category</h3>
            </div>
            <div class="chart-container-donut">
                <div class="donut-chart-wrapper">
                    <canvas id="categoryDonutChart"></canvas>
                </div>
                <div class="donut-legend" id="donut-legend">
                    <!-- Legend generated via JS -->
                </div>
            </div>
        </div>
        <div class="card chart-card">
            <div class="card-header">
                <h3>Budget Forecast</h3>
                <span class="badge badge-purple">SAFE</span>
            </div>
            <p class="chart-subtitle">Projected vs Actual spend timeline</p>
            <div class="chart-container-bar">
                <canvas id="forecastBarChart"></canvas>
            </div>
        </div>
    </div>
</main>

<!-- Expense Modal -->
<div id="expenseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Add Expense</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="expenseForm">
                <input type="hidden" id="expense_id" name="id">
                
                <div class="form-group">
                    <label for="vendor_item_name">Vendor / Item Name *</label>
                    <input type="text" id="vendor_item_name" name="vendor_item_name" required placeholder="e.g. Grand Plaza Hotel">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_logged">Date *</label>
                        <input type="date" id="date_logged" name="date_logged" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_cost">Estimated Cost *</label>
                        <input type="number" step="0.01" id="estimated_cost" name="estimated_cost" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="actual_cost">Actual Cost</label>
                        <input type="number" step="0.01" id="actual_cost" name="actual_cost" placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="payment_status">Payment Status *</label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Partially Paid">Partially Paid</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Optional details..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary cancel-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/smart-planner/assets/js/chart.min.js"></script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
