<?php
// api/tasks.php
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch tasks, optionally by event_id
        try {
            $eventId = isset($_GET['event_id']) ? $_GET['event_id'] : null;
            if ($eventId) {
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE event_id = ? ORDER BY due_date ASC");
                $stmt->execute([$eventId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM tasks ORDER BY due_date ASC");
            }
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $tasks]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new task logic here
        echo json_encode(['status' => 'success', 'message' => 'Task created successfully']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
?>
