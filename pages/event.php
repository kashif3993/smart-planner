<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

$page_css = 'events.css';
$page_js = 'events.js';
$current_page = 'events';

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
require_once dirname(__DIR__) . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch all real events for this user from the database
$stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content events-main list-view">
    <div class="list-toolbar">
        <div class="toolbar-filters">
            <select class="filter-select">
                <option>All Event Types</option>
                <option>Corporate</option>
                <option>Wedding</option>
                <option>Charity</option>
                <option>Launch</option>
                <option>Exhibition</option>
            </select>
            <select class="filter-select">
                <option>Sort by Date (Newest)</option>
                <option>Sort by Date (Oldest)</option>
                <option>Budget (High to Low)</option>
            </select>
        </div>
        <div class="view-toggles">
            <button class="btn btn-outline btn-icon active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </button>
            <button class="btn btn-outline btn-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
        </div>
    </div>

    <div class="events-grid">
        <?php if (empty($events)): ?>
        <div class="empty-state-full">
            <div class="empty-icon">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3>No Events Yet</h3>
            <p>You haven't created any events. Start planning your first event now.</p>
            <a href="javascript:void(0)" onclick="openEventModal()" class="btn btn-primary" style="text-decoration:none;">+ Create Your First Event</a>
        </div>
        <?php else: ?>

        <?php foreach ($events as $evt):
            $progress = $evt['planning_progress'] ?? 0;
            $type = strtoupper($evt['event_type'] ?? 'Event');
            $evtDate = new DateTime($evt['event_date']);
            $today = new DateTime();
            $diff = (int)$today->diff($evtDate)->format('%r%a');
            $daysRem = $diff > 0 ? $diff : 0;
            $currency = 'PKR';
        ?>
        <div class="card event-grid-card" onclick="window.location.href='/smart-planner/events?id=<?php echo $evt['id']; ?>'" style="cursor:pointer;">
            <div class="card-top-row">
                <span class="badge badge-type"><?php echo htmlspecialchars($type); ?></span>
                <button class="icon-btn edit-icon" onclick='event.stopPropagation(); openEventModal(<?php echo json_encode($evt, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)' title="Edit Event">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
            </div>
            <h3><?php echo htmlspecialchars($evt['event_name']); ?></h3>

            <div class="grid-card-meta">
                <div class="meta-col">
                    <span class="meta-label">Date</span>
                    <span class="meta-value"><?php echo date('M d, Y', strtotime($evt['event_date'])); ?></span>
                </div>
                <div class="meta-col">
                    <span class="meta-label">Budget</span>
                    <span class="meta-value"><?php echo $currency . ' ' . number_format($evt['total_budget']); ?></span>
                </div>
            </div>

            <div class="progress-row">
                <span class="progress-label">Planning Progress</span>
                <span class="progress-val purple-text"><?php echo $progress; ?>%</span>
            </div>
            <div class="progress-bar-thin">
                <div class="fill purple-bg" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>

            <div class="grid-card-footer">
                <div class="days-left">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo $daysRem; ?> Days Remaining
                </div>
                <div class="avatars">
                    <div class="avatar avatar-initial"><?php echo strtoupper(substr($evt['event_name'], 0, 1)); ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="card event-grid-card plan-new-card" onclick="openEventModal()">
            <div class="new-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
            <h3>Plan New Event</h3>
            <p>Start drafting your next masterpiece with our premium planning tools.</p>
            <button class="btn btn-primary" onclick="event.stopPropagation(); openEventModal()">Create Event</button>
        </div>

        <?php endif; ?>
    </div>
</main>

<?php 
require_once dirname(__DIR__) . '/includes/event_modal.php';
require_once dirname(__DIR__) . '/includes/footer.php'; 
?>
