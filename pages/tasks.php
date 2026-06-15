<?php
$page_css = 'tasks.css';
$page_js = 'tasks.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';
?>

<div class="container">
    <h1>Tasks</h1>
    <p>Manage and track all event tasks.</p>
    
    <div class="tasks-list">
        <p>No tasks currently. They will appear here once you create them.</p>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
