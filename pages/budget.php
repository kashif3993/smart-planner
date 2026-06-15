<?php
$page_css = 'budget.css';
$page_js = 'budget.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';
?>

<div class="container">
    <h1>Budget Tracker</h1>
    <p>Keep track of your expenses and event budget.</p>
    
    <div class="budget-summary">
        <p>No budget data available.</p>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
