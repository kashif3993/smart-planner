<?php
// api/events.php
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch events
        try {
            $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $events]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new event logic here
        echo json_encode(['status' => 'success', 'message' => 'Event created successfully']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
?>
