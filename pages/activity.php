<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

$page_css = 'activity.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/aside.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch combined activity log dynamically from existing tables
// We use updated_at to track changes and created_at for initial creations
$stmt = $pdo->prepare("
    SELECT 'Event' as category, 'Created Event' as action_type, event_name as details, e.event_name as event_name, e.created_at as time_logged 
    FROM events e WHERE user_id = ?
    UNION ALL
    SELECT 'Task' as category, CONCAT('Task ', t.status) as action_type, task_name as details, e.event_name as event_name, t.updated_at as time_logged 
    FROM tasks t JOIN events e ON t.event_id = e.id WHERE e.user_id = ?
    UNION ALL
    SELECT 'Expense' as category, 'Expense Logged' as action_type, CONCAT(vendor_item_name, ' (', actual_cost, ')') as details, e.event_name as event_name, x.updated_at as time_logged 
    FROM expenses x JOIN events e ON x.event_id = e.id WHERE e.user_id = ?
    ORDER BY time_logged DESC
    LIMIT 100
");
$stmt->execute([$user_id, $user_id, $user_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group activities by date
$grouped_activities = [];
foreach ($activities as $act) {
    $date = date('Y-m-d', strtotime($act['time_logged']));
    $grouped_activities[$date][] = $act;
}
?>

<main class="main-content activity-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Activity Log</h1>
            <p>Track all updates, task completions, and expenses across your events in real-time.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" style="background: white;" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Export Log
            </button>
        </div>
    </div>

    <div class="activity-container">
        <?php if(empty($grouped_activities)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <h3>No Activity Yet</h3>
                <p>Start planning your events, creating tasks, or logging expenses to see them appear here.</p>
            </div>
        <?php else: ?>
            <div class="activity-timeline">
                <?php foreach($grouped_activities as $date => $logs): ?>
                    <div class="timeline-date-header">
                        <?php 
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            if ($date === $today) echo "Today";
                            elseif ($date === $yesterday) echo "Yesterday";
                            else echo date('F j, Y', strtotime($date));
                        ?>
                    </div>
                    <div class="timeline-group">
                        <?php foreach($logs as $log): 
                            $time = date('g:i A', strtotime($log['time_logged']));
                            $cat = $log['category'];
                            $icon_class = 'bg-gray';
                            $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
                            
                            if ($cat === 'Event') {
                                $icon_class = 'bg-light-purple';
                                $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
                            } elseif ($cat === 'Task') {
                                if (strpos($log['action_type'], 'Completed') !== false) {
                                    $icon_class = 'bg-light-green';
                                    $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                                } else {
                                    $icon_class = 'bg-light-blue';
                                    $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>';
                                }
                            } elseif ($cat === 'Expense') {
                                $icon_class = 'bg-light-teal';
                                $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>';
                            }
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-line"></div>
                                <div class="timeline-icon <?php echo $icon_class; ?>">
                                    <?php echo $icon_svg; ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-title">
                                            <strong><?php echo htmlspecialchars($log['action_type']); ?>:</strong> 
                                            <?php echo htmlspecialchars($log['details']); ?>
                                        </div>
                                        <div class="timeline-time"><?php echo $time; ?></div>
                                    </div>
                                    <div class="timeline-meta">
                                        <span class="meta-tag"><?php echo htmlspecialchars($log['event_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
