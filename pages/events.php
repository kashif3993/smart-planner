<?php
$page_css = 'events.css';
$page_js = 'events.js';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';
?>

<div class="container">
    <h1>Events</h1>
    <p>Manage all your events here.</p>
    <a href="#" class="btn">Create New Event</a>
    
    <div class="events-list">
        <p>No events found. Start by creating one!</p>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
