<?php
// ============================================================
//  api/events.php — Events CRUD API
// ============================================================
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
    if ($action === 'list') {
        try {
            // Get all events for the user
            $stmt = $pdo->prepare("SELECT e.*, 
                (SELECT COUNT(*) FROM tasks WHERE event_id = e.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE event_id = e.id AND status = 'Completed') as completed_tasks
                FROM events e WHERE e.user_id = ? ORDER BY e.event_date ASC");
            $stmt->execute([$user_id]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $events]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid GET action']);
    }
    exit;
}

// Write actions require POST
if ($method === 'POST') {
    switch ($action) {
        case 'create':
            createEvent($pdo, $user_id);
            break;
        case 'update':
            updateEvent($pdo, $user_id);
            break;
        case 'delete':
            deleteEvent($pdo, $user_id);
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

function createEvent($pdo, $user_id) {
    try {
        $totalBudget = $_POST['total_budget'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO events 
            (user_id, event_name, event_type, custom_event_type, event_date, guest_count, venue_name, location, total_budget, description, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $_POST['event_name'] ?? 'Untitled Event',
            $_POST['event_type'] ?? 'Custom',
            $_POST['custom_event_type'] ?? null,
            $_POST['event_date'] ?? date('Y-m-d'),
            $_POST['guest_count'] ?? 0,
            $_POST['venue_name'] ?? null,
            $_POST['location'] ?? null,
            $totalBudget,
            $_POST['description'] ?? null,
            $_POST['status'] ?? 'Planning'
        ]);
        
        $eventId = $pdo->lastInsertId();
        
        // --- Call AI Service ---
        require_once dirname(__DIR__) . '/api/ai_service.php';
        
        $customType = $_POST['custom_event_type'] ?? '';
        $eventType = !empty($customType) ? $customType : ($_POST['event_type'] ?? 'Event');
        $guestCount = $_POST['guest_count'] ?? 0;
        $description = $_POST['description'] ?? '';
        
        $aiData = generateEventData($eventType, $guestCount, $totalBudget, $description);
        
        // 1. Insert AI Vendor Categories
        if (isset($aiData['vendor_categories']) && is_array($aiData['vendor_categories'])) {
            $stmtCat = $pdo->prepare("INSERT INTO vendor_categories (event_id, category_name, allocated_amount, suggested_percentage) VALUES (?, ?, ?, ?)");
            foreach ($aiData['vendor_categories'] as $cat) {
                $pct = (float)($cat['pct'] ?? 0);
                $name = $cat['name'] ?? 'Vendor';
                $allocated = ($totalBudget * $pct) / 100;
                $stmtCat->execute([$eventId, $name, $allocated, $pct]);
            }
        }
        
        // 2. Insert AI Tasks
        if (isset($aiData['tasks']) && is_array($aiData['tasks'])) {
            $stmtTask = $pdo->prepare("INSERT INTO tasks (event_id, task_name, phase, status, priority, source) VALUES (?, ?, ?, 'Pending', 'Medium', 'AI')");
            foreach ($aiData['tasks'] as $task) {
                $phase = $task['phase'] ?? 'Pre-Planning';
                $taskName = $task['task_name'] ?? 'Task';
                // Enforce valid phase
                if (!in_array($phase, ['Pre-Planning', 'Preparation', 'Day-Of'])) {
                    $phase = 'Preparation';
                }
                $stmtTask->execute([$eventId, $taskName, $phase]);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Event created successfully', 'id' => $eventId]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create event: ' . $e->getMessage()]);
    }
}

function updateEvent($pdo, $user_id) {
    $event_id = $_POST['id'] ?? null;
    if (!$event_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Event ID required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE events SET 
            event_name = ?, event_type = ?, custom_event_type = ?, event_date = ?, 
            guest_count = ?, venue_name = ?, location = ?, total_budget = ?, 
            description = ?, status = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $_POST['event_name'] ?? 'Untitled Event',
            $_POST['event_type'] ?? 'Custom',
            $_POST['custom_event_type'] ?? null,
            $_POST['event_date'] ?? date('Y-m-d'),
            $_POST['guest_count'] ?? 0,
            $_POST['venue_name'] ?? null,
            $_POST['location'] ?? null,
            $_POST['total_budget'] ?? 0,
            $_POST['description'] ?? null,
            $_POST['status'] ?? 'Planning',
            $event_id,
            $user_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Event updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No changes made or event not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update event: ' . $e->getMessage()]);
    }
}

function deleteEvent($pdo, $user_id) {
    $event_id = $_POST['id'] ?? null;
    if (!$event_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Event ID required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Event deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Event not found or unauthorized']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete event']);
    }
}
