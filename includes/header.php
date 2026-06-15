<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Event Planner</title>
    <link rel="stylesheet" href="/smart_event_planner/assets/css/style.css">
    <?php if (isset($page_css)): ?>
    <link rel="stylesheet" href="/smart_event_planner/assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Smart Event Planner</div>
            <ul>
                <li><a href="/smart_event_planner/index.php">Dashboard</a></li>
                <li><a href="/smart_event_planner/pages/events.php">Events</a></li>
                <li><a href="/smart_event_planner/pages/tasks.php">Tasks</a></li>
                <li><a href="/smart_event_planner/pages/budget.php">Budget</a></li>
            </ul>
        </nav>
    </header>
    <main>
