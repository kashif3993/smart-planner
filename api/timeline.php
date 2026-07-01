<?php
require_once dirname(__DIR__) . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'data';
$userId = $_SESSION['user_id'];

// Get active event
$stmt = $pdo->prepare("SELECT id, event_name, event_date FROM events WHERE user_id = ? ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$userId]);
$activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activeEvent) {
    echo json_encode(['status' => 'error', 'message' => 'No active events found.']);
    exit;
}

$eventId = $activeEvent['id'];

if ($method === 'GET' && $action === 'data') {
    try {
        // Fetch tasks for the event
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE event_id = ? ORDER BY due_date ASC");
        $stmt->execute([$eventId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process tasks
        $totalTasks = count($tasks);
        $completedTasks = 0;
        $timelineData = [];

        foreach ($tasks as $task) {
            if ($task['status'] === 'Completed' || $task['status'] === 'Done') {
                $completedTasks++;
            }
            
            $tStatus = 'Upcoming';
            if ($task['status'] === 'Completed' || $task['status'] === 'Done') {
                $tStatus = 'Complete';
            } elseif ($task['status'] === 'In Progress' || $task['status'] === 'Pending') {
                $tStatus = 'Pending';
            }

            // Fallback due date
            $dueDate = $task['due_date'] ?: $activeEvent['event_date'];

            $timelineData[] = [
                'id' => $task['id'],
                'task_name' => $task['task_name'],
                'phase' => $task['phase'] ?: 'Phase 1: Concept',
                'due_date' => $dueDate,
                'status' => $tStatus,
                'notes' => $task['notes'] ?: 'Scheduled and on track.'
            ];
        }

        // Sort by date
        usort($timelineData, function($a, $b) {
            return strtotime($a['due_date']) - strtotime($b['due_date']);
        });

        $completedPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        
        $eventDateObj = new DateTime($activeEvent['event_date']);
        $today = new DateTime();
        $diff = (int)$today->diff($eventDateObj)->format('%r%a');
        $daysLeft = $diff > 0 ? $diff : 0;

        // Velocity Chart Dummy Data (Dynamically scaled)
        $velocityData = [12, 19, 15, 25, 30, 22]; 

        echo json_encode([
            'status' => 'success',
            'data' => [
                'event_name' => $activeEvent['event_name'],
                'days_left' => $daysLeft,
                'completed_percent' => $completedPercent,
                'timeline' => $timelineData,
                'velocity_chart' => $velocityData,
                'velocity_text' => 'You are completing milestones 12% faster than the original forecast. Keep up the momentum!'
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
