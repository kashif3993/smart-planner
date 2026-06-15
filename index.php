<?php
$page_css = 'dashboard.css';
$page_js = 'dashboard.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
?>

<div class="container">
    <h1>Dashboard</h1>
    <p>Welcome to the Smart Event Planner. Overview of your upcoming events and pending tasks.</p>
    
    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Total Events</h3>
            <p>0</p>
        </div>
        <div class="widget">
            <h3>Pending Tasks</h3>
            <p>0</p>
        </div>
        <div class="widget">
            <h3>Total Budget</h3>
            <p>$0.00</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
