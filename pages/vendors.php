<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login');
    exit;
}

$page_css = 'categories.css';
$page_js = 'categories.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
?>

<main class="main-content categories-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Vendors</h1>
            <p>Manage your vendor allocation and monitor budget consumption across departments.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary icon-btn-text" id="filter-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg> Filter
            </button>
            <button class="btn btn-secondary icon-btn-text" id="export-btn" onclick="window.print()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Report
            </button>
        </div>
    </div>

    <!-- Category Cards Grid -->
    <div class="categories-grid" id="categories-container">
        <!-- Loaded via JS -->
    </div>

    <!-- Charts and Insights Section -->
    <div class="charts-insights-section">
        <div class="card chart-card">
            <div class="card-header">
                <h3>Budget Allocation Mix</h3>
                <div class="chart-legend-top">
                    <span class="legend-item"><span class="legend-dot spent"></span>Spent</span>
                    <span class="legend-item"><span class="legend-dot planned"></span>Planned</span>
                </div>
            </div>
            <div class="chart-container-bar">
                <canvas id="allocationMixChart"></canvas>
            </div>
        </div>
        
        <div class="card insights-card">
            <div class="card-header">
                <h3>Smart Insights</h3>
            </div>
            <div class="insights-list" id="insights-container">
                <!-- Loaded via JS -->
            </div>
            <button class="btn btn-dark w-100 mt-auto">Run Optimization</button>
        </div>
    </div>
</main>

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">New Category</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" id="category_id" name="id">
                
                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" required placeholder="e.g. Venue, Catering, Decor">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="allocated_amount">Budget Cap (Allocated) *</label>
                        <input type="number" step="0.01" id="allocated_amount" name="allocated_amount" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="suggested_percentage">Suggested % (Optional)</label>
                        <input type="number" step="0.1" id="suggested_percentage" name="suggested_percentage" placeholder="10">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Define this department..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary cancel-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/smart-planner/assets/js/chart.min.js"></script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
