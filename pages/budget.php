<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login');
    exit;
}

$page_css = 'budget.css';
$page_js = 'budget.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
?>

<main class="main-content budget-analytics-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Budget Analytics</h1>
            <p id="event-subtitle">Track expenses and manage allocations...</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary icon-btn-text" onclick="window.print()">
                <i class="fa-solid fa-download"></i> Export Report
            </button>
            <button class="btn btn-primary icon-btn-text" onclick="window.location.href='/smart-planner/expenses'">
                <i class="fa-solid fa-plus"></i> Add Expense
            </button>
        </div>
    </div>

    <!-- KPI Cards Grid -->
    <div class="metrics-grid">
        <div class="card metric-card">
            <div class="card-icon blue"><i class="fa-solid fa-money-bill"></i></div>
            <div class="metric-info">
                <span class="label">Total Budget <span class="badge blue-badge">+12% from last month</span></span>
                <h2 id="total-budget">0.00</h2>
            </div>
        </div>
        <div class="card metric-card">
            <div class="card-icon purple"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="metric-info">
                <span class="label">Total Spent <span class="badge red-badge" id="velocity-badge" style="display:none;">High Velocity</span></span>
                <h2 id="total-spent">0.00</h2>
            </div>
        </div>
        <div class="card metric-card">
            <div class="card-icon teal"><i class="fa-solid fa-building-columns"></i></div>
            <div class="metric-info">
                <span class="label">Remaining Budget</span>
                <h2 id="remaining-budget">0.00</h2>
            </div>
        </div>
        <div class="card metric-card">
            <div class="card-icon blue"><i class="fa-solid fa-chart-line"></i></div>
            <div class="metric-info">
                <span class="label">Budget Health <span class="badge green-badge" style="float:right;">Safe Zone</span></span>
                <div style="display:flex; align-items:center; gap: 1rem; margin-top: 0.5rem;">
                    <h2 id="health-pct" style="margin:0;">0%</h2>
                    <div class="progress-bar-bg" style="flex:1;">
                        <div class="progress-fill" id="health-bar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Middle Row -->
    <div class="charts-row">
        <!-- Donut Chart -->
        <div class="card chart-card donut-card">
            <div class="card-header">
                <h3>Allocation by Category</h3>
                <i class="fa-solid fa-ellipsis"></i>
            </div>
            <div class="chart-container-donut">
                <canvas id="donutChart"></canvas>
                <div class="donut-center-text">
                    <span class="muted">Top Category</span>
                    <strong id="top-category-name">-</strong>
                </div>
            </div>
            <div class="donut-legend" id="donut-legend">
                <!-- Populated via JS -->
            </div>
        </div>

        <!-- Horizontal Bar Chart -->
        <div class="card chart-card bar-card">
            <div class="card-header">
                <div>
                    <h3>Actual vs. Estimated</h3>
                    <p class="muted-text">Comparison across categories</p>
                </div>
                <div class="legend-custom">
                    <span class="legend-dot" style="background:#bfdbfe;"></span> Estimated
                    <span class="legend-dot" style="background:#4f46e5; margin-left: 10px;"></span> Actual
                </div>
            </div>
            <div class="chart-container-bar-horiz" id="bars-container">
                <!-- Custom HTML Bars injected via JS -->
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="charts-row bottom-row">
        <!-- Line Chart -->
        <div class="card chart-card line-card">
            <div class="card-header">
                <div>
                    <h3>Spending Trend</h3>
                    <p class="muted-text">Daily expenditure over the last 30 days</p>
                </div>
                <select class="form-select" style="width:auto;"><option>Last 30 Days</option></select>
            </div>
            <div class="chart-container-line">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <!-- Alerts Panel -->
        <div class="card alerts-card">
            <div class="alerts-header">
                <div class="alert-icon-main"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h3>Budget Alerts</h3>
            </div>
            <div class="alerts-list" id="alerts-container">
                <!-- Populated via JS -->
            </div>
        </div>
    </div>
</main>

<script src="/smart-planner/assets/js/chart.min.js"></script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
