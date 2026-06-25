<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

$user_id = $_SESSION['user_id'];
$event_id = $_GET['id'] ?? null;

// Always fetch an event to display the detail view. If no ID is provided, grab the most recent one.
if (!$event_id) {
    $stmt = $pdo->prepare("SELECT id FROM events WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $event_id = $stmt->fetchColumn();
}

$event = null;
if ($event_id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If no event found, redirect back to the list
if (!$event) {
    header('Location: /smart-planner/event');
    exit;
}

$page_css = 'event.css';
$page_js = 'event.js';
$current_page = 'events';

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';

$total_spent = 0;
$tasks = [];

if (!empty($event['id'])) {
    $exp_stmt = $pdo->prepare("SELECT SUM(actual_cost) as total_spent FROM expenses WHERE event_id = ?");
    $exp_stmt->execute([$event['id']]);
    $expenses_result = $exp_stmt->fetch(PDO::FETCH_ASSOC);
    $total_spent = $expenses_result['total_spent'] ?? 0;
    
    $task_stmt = $pdo->prepare("SELECT * FROM tasks WHERE event_id = ? ORDER BY FIELD(phase, 'Pre-Planning', 'Preparation', 'Day-Of'), due_date ASC");
    $task_stmt->execute([$event['id']]);
    $tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_budget = $event['total_budget'];
$remaining_budget = $total_budget - $total_spent;
$budget_status = ($remaining_budget >= 0) ? 'On Track' : 'Over Budget';
$budget_status_class = ($remaining_budget >= 0) ? 'badge-success' : 'badge-danger';

$event_date = new DateTime($event['event_date']);
$today = new DateTime();
$days_remaining = $today->diff($event_date)->format('%r%a');
if ($days_remaining < 0) {
    $days_text = "Past";
    $days_number = abs($days_remaining);
} else {
    $days_text = "Days";
    $days_number = $days_remaining;
}

$grouped_tasks = ['Pre-Planning' => [], 'Preparation' => [], 'Day-Of' => []];
foreach ($tasks as $task) {
    $phase = $task['phase'] ?? 'Pre-Planning';
    if (!isset($grouped_tasks[$phase])) $grouped_tasks['Pre-Planning'][] = $task;
    else $grouped_tasks[$phase][] = $task;
}
?>
    <main class="main-content events-main detail-view">
        <div class="event-header">
            <div class="breadcrumb">
                <a href="/smart-planner/events">Events</a> &rsaquo; <span><?php echo htmlspecialchars($event['event_name']); ?></span>
            </div>
            <div class="event-title-row">
                <div class="event-title-info">
                    <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>
                    <p><?php echo htmlspecialchars($event['description'] ?? 'Managing corporate excellence and attendee luxury.'); ?></p>
                </div>
                <div class="event-actions">
                    <button class="btn btn-outline share-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        Share
                    </button>
                    <a href="/smart-planner/event" class="btn btn-primary edit-details-btn" style="text-decoration:none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        Edit Details
                    </a>
                </div>
            </div>
        </div>

        <div class="event-kpi-grid">
            <div class="card kpi-card">
                <div class="kpi-header">COUNTDOWN</div>
                <div class="kpi-body countdown-body">
                    <div class="kpi-value-large purple-text"><?php echo $days_number; ?></div>
                    <div class="kpi-label"><?php echo $days_text; ?></div>
                </div>
                <div class="kpi-footer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo $event_date->format('M d, Y'); ?>
                </div>
            </div>

            <div class="card kpi-card">
                <div class="kpi-header">GUEST CAPACITY</div>
                <div class="kpi-body flex-between">
                    <div class="kpi-stats">
                        <span class="kpi-value-medium"><?php echo (int)$event['guest_count']; ?></span>
                        <span class="kpi-subtext">confirmed</span>
                    </div>
                    <div class="kpi-icon bg-light-purple">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
                <div class="kpi-footer-progress">
                    <?php
                        // Progress based on tasks completion
                        $total_tasks = count($tasks);
                        $done_tasks = count(array_filter($tasks, fn($t) => $t['status'] === 'Completed'));
                        $task_percent = $total_tasks > 0 ? min(100, round(($done_tasks / $total_tasks) * 100)) : 0;
                    ?>
                    <div class="progress-bar-thin"><div class="fill purple-bg" style="width: <?php echo $task_percent; ?>%;"></div></div>
                    <div class="progress-label"><?php echo $task_percent; ?>% tasks completed</div>
                </div>
            </div>

            <div class="card kpi-card venue-card">
                <div class="venue-icon-area">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div class="venue-details">
                    <div class="kpi-header">VENUE</div>
                    <div class="venue-name"><?php echo htmlspecialchars($event['venue_name'] ?: 'Not specified'); ?></div>
                    <?php if (!empty($event['location'])): ?>
                    <div class="venue-location">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo htmlspecialchars($event['location']); ?>
                    </div>
                    <?php endif; ?>
                    <a href="https://maps.google.com/?q=<?php echo urlencode(($event['venue_name'] ?? '') . ' ' . ($event['location'] ?? '')); ?>" target="_blank" class="view-map-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        View on Map
                    </a>
                </div>
            </div>

            <div class="card kpi-card">
                <div class="kpi-header">BUDGET UTILIZATION</div>
                <div class="kpi-body flex-between">
                    <?php
                        $currency_sym = 'PKR ';
                        $budget_display = $total_budget >= 1000
                            ? $currency_sym . str_replace('.0', '', number_format($total_budget / 1000, 1)) . 'k'
                            : $currency_sym . number_format($total_budget, 0);
                        $spent_display = $total_spent >= 1000
                            ? $currency_sym . str_replace('.0', '', number_format($total_spent / 1000, 1)) . 'k'
                            : $currency_sym . number_format($total_spent, 0);
                        $rem_display = $remaining_budget >= 1000
                            ? $currency_sym . str_replace('.0', '', number_format(max(0,$remaining_budget) / 1000, 1)) . 'k'
                            : $currency_sym . number_format(max(0,$remaining_budget), 0);
                    ?>
                    <div class="kpi-stats">
                        <div class="kpi-value-medium"><?php echo $spent_display; ?></div>
                        <div class="kpi-subtext" style="margin-top: 2px;">Total Spent</div>
                    </div>
                    <div class="kpi-icon bg-light-teal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg>
                    </div>
                </div>
                <div class="kpi-footer budget-footer">
                    <span class="badge <?php echo $budget_status_class; ?>"><?php echo $budget_status; ?></span>
                    <span class="remaining-text"><?php echo $rem_display; ?> remaining</span>
                </div>
            </div>
        </div>

        <div class="card task-checklist-card">
            <div class="card-header checklist-header">
                <div class="header-titles">
                    <h2>AI Task Checklist</h2>
                    <p>Recommended actions generated by Smart Planner AI</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline filter-btn" aria-label="Filter">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    </button>
                    <button class="btn btn-primary add-task-btn" onclick="openTaskModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Task
                    </button>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="task-filter-bar">
                <div class="task-search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="taskSearchInput" placeholder="Search tasks..." oninput="filterTasks()">
                </div>
                <select id="filterPhase" onchange="filterTasks()">
                    <option value="">All Phases</option>
                    <option value="Pre-Planning">Pre-Planning</option>
                    <option value="Preparation">Preparation</option>
                    <option value="Day-Of">Day-Of</option>
                </select>
                <select id="filterPriority" onchange="filterTasks()">
                    <option value="">All Priorities</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                <select id="filterStatus" onchange="filterTasks()">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                    <option value="Skipped">Skipped</option>
                </select>
            </div>
            
            <div class="task-table-container">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>TASK NAME</th>
                            <th>PHASE</th>
                            <th>DUE DATE</th>
                            <th>PRIORITY</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (['Pre-Planning', 'Preparation', 'Day-Of'] as $phase_name): ?>
                            <?php if (!empty($grouped_tasks[$phase_name])): ?>
                                <tr class="phase-row">
                                    <td colspan="6"><?php echo strtoupper($phase_name); ?></td>
                                </tr>
                                <?php foreach ($grouped_tasks[$phase_name] as $task): ?>
                                    <?php
                                        $is_completed = ($task['status'] === 'Completed');
                                        $priority_class = strtolower($task['priority'] ?? 'medium');
                                        if ($priority_class == 'high' || $priority_class == 'critical') $priority_class = 'danger';
                                        elseif ($priority_class == 'low') $priority_class = 'info';
                                        else $priority_class = 'warning';
                                    ?>
                                    <tr class="task-row">
                                        <td>
                                            <div class="task-name-cell">
                                                <div class="task-icon bg-light-purple">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                </div>
                                                <div class="task-info">
                                                    <div class="t-name"><?php echo htmlspecialchars($task['task_name']); ?></div>
                                                    <div class="t-note"><?php echo htmlspecialchars($task['notes'] ?? 'Review details and manage requirements'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($task['phase']); ?></td>
                                        <td><?php echo (new DateTime($task['due_date']))->format('M d, Y'); ?></td>
                                        <td><span class="badge badge-<?php echo $priority_class; ?>"><?php echo htmlspecialchars($task['priority'] ?? 'Medium'); ?></span></td>
                                        <td>
                                            <div class="status-toggle">
                                                <span class="status-text"><?php echo $is_completed ? 'Done' : 'Pending'; ?></span>
                                                <label class="switch">
                                                    <input type="checkbox" class="task-status-cb" data-id="<?php echo $task['id']; ?>" <?php echo $is_completed ? 'checked' : ''; ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button class="row-action-btn edit" title="Edit" onclick='openTaskModal(<?php echo json_encode($task, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)'>
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                </button>
                                                <button class="row-action-btn delete" title="Delete" onclick="deleteTaskById(<?php echo $task['id']; ?>)">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($tasks)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No tasks found. Click "Add Task" to generate an AI plan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-pagination">
                <span>Showing <?php echo count($tasks); ?> of <?php echo count($tasks); ?> tasks</span>
                <div class="pagination-controls">
                    <button class="btn btn-outline btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></button>
                    <span class="page-num">1</span>
                    <button class="btn btn-outline btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></button>
                </div>
            </div>
        </div>

        <div class="card insight-card">
            <div class="insight-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="insight-content">
                <h3>Smart Planning Insight</h3>
                <p>Based on previous events of this type, costs usually increase by 15% in the final month. Consider locking in your beverage package now to save an estimated <strong>$4,200</strong>.</p>
            </div>
            <button class="btn btn-white">Apply Optimization</button>
        </div>
    </main>

<?php 
require_once dirname(__DIR__) . '/includes/event_modal.php';
require_once dirname(__DIR__) . '/includes/task_modal.php';
require_once dirname(__DIR__) . '/includes/footer.php'; 
?>
