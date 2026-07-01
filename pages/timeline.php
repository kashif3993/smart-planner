<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login');
    exit;
}

$page_css = 'timeline.css';
$page_js = 'timeline.js';
$current_page = 'timeline';

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
?>

<main class="main-content timeline-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Event Timeline</h1>
            <p>Project: <strong id="timeline-project-name" class="purple-text">Loading...</strong> &bull; Track your milestones and logistics in a real-time chronological view.</p>
        </div>
        <div class="header-actions">
            <div class="kpi-badge">
                <div class="kpi-icon purple-bg">
                    <i class="fa-solid fa-clock" style="font-size: 18px;"></i>
                </div>
                <div class="kpi-text">
                    <span class="kpi-label">DAYS LEFT</span>
                    <span class="kpi-val" id="kpi-days-left">--</span>
                </div>
            </div>
            <div class="kpi-badge">
                <div class="kpi-icon blue-bg">
                    <i class="fa-solid fa-check-circle" style="font-size: 18px;"></i>
                </div>
                <div class="kpi-text">
                    <span class="kpi-label">COMPLETED</span>
                    <span class="kpi-val" id="kpi-completed">--%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="timeline-toolbar">
        <div class="view-toggles">
            <button class="toggle-btn active" id="btn-vertical-view">Vertical View</button>
            <button class="toggle-btn" id="btn-horizontal-view">Horizontal</button>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-outline" style="background: white;">
                <i class="fa-solid fa-list-ul" style="margin-right: 5px;"></i>
                All Statuses
            </button>
            <button class="btn btn-outline" style="background: white;" onclick="window.print()">
                <i class="fa-regular fa-file-pdf" style="margin-right: 5px;"></i>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Timeline Container -->
    <div id="timeline-container" class="timeline-wrapper vertical">
        <!-- Generated via JS -->
    </div>

    <!-- Bottom Analytics Section -->
    <div class="timeline-analytics">
        <div class="card velocity-card">
            <h3>Timeline Velocity</h3>
            <p id="velocity-text">Loading...</p>
            <div class="velocity-chart-wrapper">
                <canvas id="velocityChart"></canvas>
            </div>
        </div>
       
    </div>
</main>

<script src="/smart-planner/assets/js/chart.min.js"></script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
