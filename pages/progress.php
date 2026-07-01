<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

$page_css = 'progress.css';
$page_js = 'progress.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/aside.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// Get the latest or most active event for this user
$stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$user_id]);
$current_event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_event) {
    // Fallback to latest created event
    $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $current_event = $stmt->fetch(PDO::FETCH_ASSOC);
}

$event_name = $current_event ? $current_event['event_name'] : 'No Active Event';
$event_id = $current_event ? $current_event['id'] : 0;

// Fetch task stats for this event
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status != 'Completed' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN due_date < CURDATE() AND status != 'Completed' THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks 
    WHERE event_id = ?
");
$stmt->execute([$event_id]);
$task_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_tasks = $task_stats['total_tasks'] ?? 0;
$completed_tasks = $task_stats['completed_tasks'] ?? 0;
$pending_tasks = $task_stats['pending_tasks'] ?? 0;
$overdue_tasks = $task_stats['overdue_tasks'] ?? 0;

$progress_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$ring_offset = 628 - (628 * $progress_percent / 100);

// Get upcoming tasks for immediate actions
$stmt = $pdo->prepare("SELECT task_name, due_date, status, phase FROM tasks WHERE event_id = ? AND status != 'Completed' ORDER BY due_date ASC LIMIT 3");
$stmt->execute([$event_id]);
$immediate_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Total Expenses
$stmt = $pdo->prepare("SELECT SUM(actual_cost) as total_spent FROM expenses WHERE event_id = ?");
$stmt->execute([$event_id]);
$total_spent = $stmt->fetchColumn() ?: 0;

// Fetch Latest Activity (latest completed task)
$stmt = $pdo->prepare("SELECT task_name, phase FROM tasks WHERE event_id = ? AND status = 'Completed' ORDER BY id DESC LIMIT 1");
$stmt->execute([$event_id]);
$latest_activity = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Vendor Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vendor_categories WHERE event_id = ?");
$stmt->execute([$event_id]);
$vendor_count = $stmt->fetchColumn() ?: 0;

// Generate Trend Data dynamically (Tasks due next 7 days)
$trend_data = [];
$trend_labels = [];
for($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $trend_labels[] = date('D', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE event_id = ? AND due_date = ?");
    $stmt->execute([$event_id, $date]);
    $trend_data[] = (int)$stmt->fetchColumn();
}
?>

<main class="main-content progress-main">
    <div class="progress-topbar">
        <div class="progress-titles">
            <span class="project-health-badge">PROJECT HEALTH</span>
            <h1>Strategic Progress Dashboard</h1>
            <p>Real-time completion metrics for '<?php echo htmlspecialchars($event_name); ?>'</p>
        </div>
        <div class="progress-actions">
            <button class="btn-filter"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg> Filter</button>
            <button class="btn-share"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share Report</button>
        </div>
    </div>

    <div class="progress-grid">
        <!-- Task Completion Efficiency -->
        <div class="progress-card efficiency-card">
            <h3>TASK COMPLETION EFFICIENCY</h3>
            <div class="efficiency-chart">
                <svg width="220" height="220" viewBox="0 0 220 220">
                    <circle class="eff-ring-track" cx="110" cy="110" r="100" />
                    <circle class="eff-ring-fill" cx="110" cy="110" r="100" style="stroke-dasharray: 628; stroke-dashoffset: <?php echo $ring_offset; ?>;" />
                </svg>
                <div class="eff-center-text">
                    <h2><?php echo $progress_percent; ?>%</h2>
                    <span>TOTAL<br>PROGRESS</span>
                </div>
            </div>
            <div class="eff-stats">
                <div class="eff-stat-box">
                    <span>Milestones</span>
                    <strong><?php echo $completed_tasks; ?>/<?php echo $total_tasks; ?></strong>
                </div>
                <div class="eff-stat-box highlight">
                    <span>Efficiency</span>
                    <strong>+4.2%</strong>
                </div>
            </div>
        </div>

        <!-- 4 Small KPIs -->
        <div class="progress-kpis">
            <div class="p-kpi-card">
                <div class="kpi-icon-wrap bg-light-cyan">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="kpi-tag text-green">+12%</span>
                <div class="p-kpi-info">
                    <span>Completed Tasks</span>
                    <h3><?php echo $completed_tasks; ?></h3>
                </div>
            </div>
            <div class="p-kpi-card">
                <div class="kpi-icon-wrap bg-light-purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <span class="kpi-tag text-blue">- stable</span>
                <div class="p-kpi-info">
                    <span>Pending</span>
                    <h3><?php echo $pending_tasks; ?></h3>
                </div>
            </div>
            <div class="p-kpi-card">
                <div class="kpi-icon-wrap bg-light-red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <span class="kpi-tag bg-light-red-tag text-red">&#9888; urgent</span>
                <div class="p-kpi-info">
                    <span>Overdue</span>
                    <h3 class="text-red"><?php echo $overdue_tasks; ?></h3>
                </div>
            </div>
            <div class="p-kpi-card">
                <div class="kpi-icon-wrap bg-light-blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                </div>
                <span class="kpi-tag bg-light-blue-tag text-blue">Expenses</span>
                <div class="p-kpi-info">
                    <span>Total Spent</span>
                    <h3><?php echo number_format($total_spent, 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Completion Trend -->
        <div class="progress-card trend-card">
            <div class="trend-header">
                <h3>COMPLETION TREND</h3>
                <div class="trend-filters">
                    <button class="active">1W</button>
                    <button>1M</button>
                    <button>ALL</button>
                </div>
            </div>
            <div class="trend-chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Immediate Actions & Latest Activity -->
        <div class="progress-right-col">
            <div class="progress-card immediate-actions">
                <div class="card-header-flex">
                    <h3>IMMEDIATE ACTIONS</h3>
                    <a href="/smart-planner/events" class="view-all">View All</a>
                </div>
                <div class="actions-list">
                    <?php if(empty($immediate_actions)): ?>
                        <p class="text-muted">No immediate actions needed.</p>
                    <?php else: ?>
                        <?php foreach($immediate_actions as $action): ?>
                            <div class="action-item">
                                <div class="action-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                                <div class="action-details">
                                    <h4><?php echo htmlspecialchars($action['task_name']); ?></h4>
                                    <p>Due: <?php echo date('M d', strtotime($action['due_date'])); ?> • <?php echo htmlspecialchars($action['phase'] ?? 'General'); ?></p>
                                </div>
                                <div class="action-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="progress-card latest-activity">
                <h3>LATEST TEAM ACTIVITY</h3>
                <div class="team-avatars">
                    <!-- Dynamic user initials instead of static images -->
                    <div class="avatar-more" style="width: 40px; height: 40px; background: var(--primary);"><?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?></div>
                </div>
                <div class="activity-card">
                    <div class="activity-info">
                        <?php if($latest_activity): ?>
                            <h4>Task Completed: <?php echo htmlspecialchars($latest_activity['task_name']); ?></h4>
                            <p>Phase: <?php echo htmlspecialchars($latest_activity['phase']); ?> • Just now</p>
                        <?php else: ?>
                            <h4>No recent activity</h4>
                            <p>Complete tasks to see them here.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Small Cards -->
        <div class="bottom-small-cards">
            <div class="p-kpi-card horizontal">
                <div class="kpi-icon-wrap bg-light-purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="p-kpi-info">
                    <h4 style="font-size: 0.8rem;">Location Configured</h4>
                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo htmlspecialchars($current_event['location'] ?? 'TBD'); ?></span>
                </div>
            </div>
            <div class="p-kpi-card horizontal">
                <div class="kpi-icon-wrap bg-light-teal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="p-kpi-info">
                    <h4 style="font-size: 0.8rem;">Active Vendors</h4>
                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $vendor_count; ?> registered</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Action Button for Smart Watch/Mobile view -->
    <button class="fab-action">+</button>
</main>

<script>
    // Pass dynamic data to JS
    window.trendData = <?php echo json_encode($trend_data); ?>;
    window.trendLabels = <?php echo json_encode($trend_labels); ?>;
</script>
<script src="/smart-planner/assets/js/chart.min.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
