<?php
// api/tasks.php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Allow reading via GET
if ($method === 'GET') {
    try {
        $eventId = $_GET['event_id'] ?? null;
        if ($eventId) {
            // Verify event belongs to user
            $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND user_id = ?");
            $stmt->execute([$eventId, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to event']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE event_id = ? ORDER BY FIELD(phase, 'Pre-Planning', 'Preparation', 'Day-Of'), due_date ASC");
            $stmt->execute([$eventId]);
        } else {
            // We usually don't fetch all tasks across events, but if needed, join to verify user
            $stmt = $pdo->prepare("SELECT t.* FROM tasks t JOIN events e ON t.event_id = e.id WHERE e.user_id = ? ORDER BY t.due_date ASC");
            $stmt->execute([$user_id]);
        }
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $tasks]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

// Write actions require POST
if ($method === 'POST') {
    switch ($action) {
        case 'create':
            createTask($pdo, $user_id);
            break;
        case 'update':
            updateTask($pdo, $user_id);
            break;
        case 'delete':
            deleteTask($pdo, $user_id);
            break;
        case 'toggle_status':
            toggleTaskStatus($pdo, $user_id);
            break;
        case 'ai_suggest':
            suggestTaskWithAI($pdo, $user_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid POST action']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------
// Helper Functions
// ---------------------------------------------------------

function verifyEventOwnership($pdo, $event_id, $user_id) {
    if (!$event_id) return false;
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    return $stmt->fetch() !== false;
}

function createTask($pdo, $user_id) {
    $event_id = $_POST['event_id'] ?? null;
    
    if (!verifyEventOwnership($pdo, $event_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid event or unauthorized']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tasks 
            (event_id, task_name, phase, due_date, priority, status, notes, source) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Manual')
        ");
        $stmt->execute([
            $event_id,
            $_POST['task_name'] ?? 'Untitled Task',
            $_POST['phase'] ?? 'Pre-Planning',
            !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            $_POST['priority'] ?? 'Medium',
            $_POST['status'] ?? 'Pending',
            $_POST['notes'] ?? null
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Task created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create task: ' . $e->getMessage()]);
    }
}

function updateTask($pdo, $user_id) {
    $task_id = $_POST['id'] ?? null;
    $event_id = $_POST['event_id'] ?? null;
    
    if (!$task_id || !verifyEventOwnership($pdo, $event_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid task/event or unauthorized']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE tasks SET 
            task_name = ?, phase = ?, due_date = ?, priority = ?, status = ?, notes = ?
            WHERE id = ? AND event_id = ?
        ");
        $stmt->execute([
            $_POST['task_name'] ?? 'Untitled Task',
            $_POST['phase'] ?? 'Pre-Planning',
            !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            $_POST['priority'] ?? 'Medium',
            $_POST['status'] ?? 'Pending',
            $_POST['notes'] ?? null,
            $task_id,
            $event_id
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Task updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update task']);
    }
}

function deleteTask($pdo, $user_id) {
    $task_id = $_POST['id'] ?? null;
    
    // Check if task exists and belongs to an event owned by user
    try {
        $stmt = $pdo->prepare("SELECT event_id FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task || !verifyEventOwnership($pdo, $task['event_id'], $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Task not found or unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        echo json_encode(['status' => 'success', 'message' => 'Task deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete task']);
    }
}

function toggleTaskStatus($pdo, $user_id) {
    $task_id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? 'Pending';
    
    try {
        $stmt = $pdo->prepare("SELECT event_id FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task || !verifyEventOwnership($pdo, $task['event_id'], $user_id)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Task not found or unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);
        echo json_encode(['status' => 'success', 'message' => 'Task status updated']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update task status']);
    }
}

function suggestTaskWithAI($pdo, $user_id) {
    require_once __DIR__ . '/ai_service.php';
    
    $event_id = $_POST['event_id'] ?? null;
    
    if (!verifyEventOwnership($pdo, $event_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid event or unauthorized']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT event_name, event_type, description FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $eventType = $event['event_type'] ?? 'Event';
        $description = $event['description'] ?? 'An upcoming event.';
        
        $suggestion = suggestSingleTask($eventType, $description);
        
        echo json_encode(['status' => 'success', 'data' => $suggestion]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'AI generation failed']);
    }
}

