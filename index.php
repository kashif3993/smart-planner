<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

$page_css = 'dashboard.css';
$page_js = 'dashboard.js';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/aside.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// 1. Total Events & Upcoming Events
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming FROM events WHERE user_id = ?");
$stmt->execute([$user_id]);
$event_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total_events = $event_stats['total'] ?? 0;
$upcoming_events = $event_stats['upcoming'] ?? 0;

$stmt = $pdo->prepare("SELECT event_name FROM events WHERE user_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$user_id]);
$next_event_name = $stmt->fetchColumn();
$next_event_text = $next_event_name ? "Next: " . htmlspecialchars($next_event_name) : "No upcoming events";

// 2. Budget Status
$stmt = $pdo->prepare("SELECT SUM(total_budget) as total_budget FROM events WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_budget = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(actual_cost) as total_spent FROM expenses x JOIN events e ON x.event_id = e.id WHERE e.user_id = ?");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetchColumn() ?: 0;

$budget_percent = $total_budget > 0 ? min(100, round(($total_spent / $total_budget) * 100)) : 0;
$budget_status_text = $budget_percent <= 100 ? "On Track" : "Over Budget";
$budget_color_class = $budget_percent <= 100 ? "teal-text" : "red-text";

// 3. Tasks Status
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN t.status != 'Completed' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN t.priority = 'High' AND t.status != 'Completed' THEN 1 ELSE 0 END) as high_priority_pending,
        SUM(CASE WHEN t.due_date = CURDATE() AND t.status != 'Completed' THEN 1 ELSE 0 END) as due_today,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks t 
    JOIN events e ON t.event_id = e.id 
    WHERE e.user_id = ?
");
$stmt->execute([$user_id]);
$task_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_tasks = $task_stats['pending_tasks'] ?? 0;
$high_priority_pending = $task_stats['high_priority_pending'] ?? 0;
$due_today = $task_stats['due_today'] ?? 0;
$total_tasks = $task_stats['total_tasks'] ?? 0;
$completed_tasks = $task_stats['completed_tasks'] ?? 0;

$global_progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$ring_offset = 502 - (502 * $global_progress / 100);

// Active Events
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id = ? AND status IN ('Planning', 'In Progress')");
$stmt->execute([$user_id]);
$active_events_count = $stmt->fetchColumn() ?: 0;

// 4. Recent/Upcoming Events List
$stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3");
$stmt->execute([$user_id]);
$recent_events_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($recent_events_list)) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY event_date DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $recent_events_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main class="main-content dashboard-main">
    <div class="dashboard-header">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></h1>
        <p>Your planning ecosystem is looking optimized for the week ahead.</p>
    </div>

    <div class="dashboard-grid">
        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="card kpi-card">
                <div class="kpi-info">
                    <span class="kpi-title">TOTAL EVENTS</span>
                    <h2><?php echo $total_events; ?></h2>
                    <span class="trend positive"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> All time</span>
                </div>
                <div class="kpi-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-info">
                    <span class="kpi-title">UPCOMING EVENTS</span>
                    <h2 class="purple-text"><?php echo $upcoming_events; ?></h2>
                    <span class="trend"><?php echo $next_event_text; ?></span>
                </div>
                <div class="kpi-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-info">
                    <span class="kpi-title">BUDGET STATUS</span>
                    <h2 class="<?php echo $budget_color_class; ?>"><?php echo $budget_status_text; ?></h2>
                    <div class="progress-bar-mini"><div class="fill" style="width: <?php echo $budget_percent; ?>%;"></div></div>
                    <span class="trend"><?php echo $budget_percent; ?>% of total budget used</span>
                </div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-info">
                    <span class="kpi-title">PENDING TASKS</span>
                    <h2 class="red-text"><?php echo $pending_tasks; ?></h2>
                    <span class="trend <?php echo $high_priority_pending > 0 ? 'red-text' : ''; ?>"><?php echo $high_priority_pending > 0 ? "! " . $high_priority_pending . " high priority" : "All good"; ?></span>
                </div>
                <div class="kpi-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e5e7eb" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
            </div>
        </div>

        <!-- Monthly Spending & Global Progress -->
        <div class="middle-section">
            <div class="card spending-chart">
                <div class="card-header">
                    <h3>Monthly Spending</h3>
                    <select class="filter-select">
                        <option>Last 6 Months</option>
                    </select>
                </div>
                <div class="chart-container" id="spending-chart-container">
                    <!-- Bars drawn via JS -->
                </div>
            </div>
            
            <div class="card global-progress">
                <h3>Global Progress</h3>
                <div class="progress-ring-container">
                    <svg class="progress-ring" width="180" height="180">
                        <circle class="ring-track" stroke-width="12" fill="transparent" r="80" cx="90" cy="90"/>
                        <circle class="ring-fill" stroke-width="12" fill="transparent" r="80" cx="90" cy="90" stroke-dasharray="502" stroke-dashoffset="<?php echo $ring_offset; ?>"/>
                    </svg>
                    <div class="progress-text">
                        <h2><?php echo $global_progress; ?>%</h2>
                        <span>COMPLETED</span>
                    </div>
                </div>
                <div class="progress-stats">
                    <div class="stat">
                        <span class="label">Active Events</span>
                        <span class="value"><?php echo sprintf("%02d", $active_events_count); ?></span>
                    </div>
                    <div class="stat">
                        <span class="label">Due Today</span>
                        <span class="value purple-text"><?php echo sprintf("%02d", $due_today); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Events & Quick Actions -->
        <div class="bottom-section">
            <div class="card recent-events">
                <div class="card-header">
                    <h3>Events</h3>
                    <a href="/smart-planner/events" class="view-all">View All</a>
                </div>
                <div class="event-list">
                    <?php if(empty($recent_events_list)): ?>
                        <p style="color: var(--text-muted); padding: 10px;">No events found.</p>
                    <?php else: ?>
                        <?php foreach($recent_events_list as $evt): 
                            $dateStr = date('M d, Y', strtotime($evt['event_date']));
                            $status = $evt['status'] ?? 'Planning';
                            $status_class = 'badge-gray';
                            if ($status == 'Planning') $status_class = 'badge-purple';
                            elseif ($status == 'In Progress') $status_class = 'badge-teal';
                            elseif ($status == 'Completed') $status_class = 'badge-success';
                            
                            $icon_class = 'bg-light-purple';
                            $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
                        ?>
                        <div class="event-item" style="cursor:pointer;" onclick="window.location.href='/smart-planner/events?id=<?php echo $evt['id']; ?>'">
                            <div class="event-icon <?php echo $icon_class; ?>"><?php echo $icon_svg; ?></div>
                            <div class="event-details">
                                <h4><?php echo htmlspecialchars($evt['event_name']); ?></h4>
                                <p><?php echo $dateStr; ?> • <?php echo htmlspecialchars($evt['location'] ?: 'No location'); ?></p>
                            </div>
                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                            <svg class="arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card quick-actions-wrapper">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <div class="action-card bg-light-purple" style="cursor:pointer;" onclick="window.location.href='/smart-planner/event?new=1'">
                        <div class="action-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                        <div class="action-text">
                            <h4>Create Event</h4>
                            <p>Start a new planning journey</p>
                        </div>
                    </div>
                    <div class="action-card bg-light-purple" style="cursor:pointer;" onclick="window.location.href='/smart-planner/event'">
                        <div class="action-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></div>
                        <div class="action-text">
                            <h4>View Events</h4>
                            <p>Manage all your events</p>
                        </div>
                    </div>
                    <div class="action-card bg-light-teal" style="cursor:pointer;" onclick="window.location.href='/smart-planner/progress'">
                        <div class="action-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg></div>
                        <div class="action-text">
                            <h4>View Progress</h4>
                            <p>Check overall statistics</p>
                        </div>
                    </div>
                </div>
                <div class="pro-tip-card">
                    <h4>PRO TIP</h4>
                    <p>Integrate calendars to sync all deadlines automatically.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
