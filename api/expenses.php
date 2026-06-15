<?php
// api/expenses.php
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch expenses, optionally by event_id
        try {
            $eventId = isset($_GET['event_id']) ? $_GET['event_id'] : null;
            if ($eventId) {
                $stmt = $pdo->prepare("SELECT * FROM expenses WHERE event_id = ? ORDER BY date_logged DESC");
                $stmt->execute([$eventId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM expenses ORDER BY date_logged DESC");
            }
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $expenses]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new expense logic here
        echo json_encode(['status' => 'success', 'message' => 'Expense logged successfully']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
?>
