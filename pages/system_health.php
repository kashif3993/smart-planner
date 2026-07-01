<?php
// pages/system_health.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resolve_id'])) {
        $stmt = $pdo->prepare("UPDATE system_errors SET resolved = 1 WHERE id = ?");
        $stmt->execute([$_POST['resolve_id']]);
        echo "Success";
        exit;
    }
    if (isset($_POST['clear_resolved'])) {
        $pdo->query("DELETE FROM system_errors WHERE resolved = 1");
        echo "Success";
        exit;
    }
}

$page_css = 'system_health.css';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/aside.php';

// Check if table exists (in case error_handler hasn't run yet)
try {
    $stmt = $pdo->query("SELECT * FROM system_errors ORDER BY id DESC LIMIT 100");
    $system_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $system_logs = []; // Table might not exist yet if no connection has been made
}

$unresolved_count = 0;
foreach ($system_logs as $log) {
    if ($log['resolved'] == 0) $unresolved_count++;
}
?>

<main class="main-content health-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>System Diagnostics & Health</h1>
            <p>Real-time interceptor for system bugs, API failures, and backend exceptions.</p>
        </div>
        <div class="header-actions">
            <?php if($unresolved_count > 0): ?>
                <span class="status-badge bg-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <?php echo $unresolved_count; ?> Critical Issues
                </span>
            <?php else: ?>
                <span class="status-badge bg-success">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    System Healthy
                </span>
            <?php endif; ?>
            <button class="btn btn-outline" onclick="clearResolved()">Clear Resolved</button>
        </div>
    </div>

    <div class="card health-card">
        <?php if(empty($system_logs)): ?>
            <div class="empty-state text-center" style="padding: 60px 20px;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" style="margin-bottom: 20px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <h3>No Errors Logged</h3>
                <p class="text-muted">The system is running perfectly smoothly. Any runtime errors, warnings, or fatal crashes will be intercepted and displayed here automatically.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="health-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Error Level</th>
                            <th>Message & Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($system_logs as $log): ?>
                            <tr class="<?php echo $log['resolved'] ? 'resolved-row' : ''; ?>" id="log-row-<?php echo $log['id']; ?>">
                                <td>
                                    <?php if($log['resolved']): ?>
                                        <span class="icon-circle success-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                                    <?php else: ?>
                                        <span class="icon-circle <?php echo $log['error_level'] === 'Warning' ? 'warning-sm' : 'danger-sm'; ?>">!</span>
                                    <?php endif; ?>
                                </td>
                                <td class="time-col"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><span class="badge <?php echo $log['error_level'] === 'Warning' ? 'badge-warning' : 'badge-danger'; ?>"><?php echo htmlspecialchars($log['error_level']); ?></span></td>
                                <td class="message-col">
                                    <strong><?php echo htmlspecialchars($log['error_message']); ?></strong>
                                    <div class="file-path text-muted mt-1" style="font-size: 0.8rem; font-family: monospace;">
                                        <?php echo htmlspecialchars($log['file_path']); ?> : Line <?php echo $log['line_num']; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!$log['resolved']): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="resolveIssue(<?php echo $log['id']; ?>)">Mark Resolved</button>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.85rem;">Resolved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function resolveIssue(id) {
    const formData = new FormData();
    formData.append('resolve_id', id);
    fetch('/smart-planner/pages/system_health.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        const row = document.getElementById('log-row-' + id);
        row.classList.add('resolved-row');
        row.querySelector('.icon-circle').className = 'icon-circle success-sm';
        row.querySelector('.icon-circle').innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
        row.querySelector('td:last-child').innerHTML = '<span class="text-muted" style="font-size:0.85rem;">Resolved</span>';
        
        // Decrease critical count visually
        const badge = document.querySelector('.status-badge.bg-danger');
        if (badge) {
            let count = parseInt(badge.textContent.match(/\d+/)[0]);
            count--;
            if (count > 0) {
                badge.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> ${count} Critical Issues`;
            } else {
                badge.className = 'status-badge bg-success';
                badge.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> System Healthy`;
            }
        }
    });
}

function clearResolved() {
    if(!confirm("Are you sure you want to clear all resolved logs?")) return;
    const formData = new FormData();
    formData.append('clear_resolved', 1);
    fetch('/smart-planner/pages/system_health.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        window.location.reload();
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
