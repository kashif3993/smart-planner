<?php
// api/expenses.php
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
$action = $_GET['action'] ?? 'list';
$userId = $_SESSION['user_id'];

// Get active event for user (for simplicity, using the nearest upcoming event or latest created)
$stmt = $pdo->prepare("SELECT id, total_budget FROM events WHERE user_id = ? ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$userId]);
$activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activeEvent) {
    echo json_encode(['status' => 'error', 'message' => 'No active events found.']);
    exit;
}

$eventId = $activeEvent['id'];
$totalBudget = $activeEvent['total_budget'] ?: 0;

switch ($method) {
    case 'GET':
        try {
            if ($action === 'categories') {
                $stmt = $pdo->prepare("SELECT id, category_name AS name FROM vendor_categories WHERE event_id = ?");
                $stmt->execute([$eventId]);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $categories]);
            }
            elseif ($action === 'metrics') {
                // Total actual cost
                $stmt = $pdo->prepare("SELECT SUM(actual_cost) FROM expenses WHERE event_id = ?");
                $stmt->execute([$eventId]);
                $totalExpenses = $stmt->fetchColumn() ?: 0;
                
                // Pending approval
                $stmt = $pdo->prepare("SELECT SUM(actual_cost) as amount, COUNT(id) as count FROM expenses WHERE event_id = ? AND payment_status = 'Pending'");
                $stmt->execute([$eventId]);
                $pending = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $remainingBudget = max(0, $totalBudget - $totalExpenses);
                $budgetPercent = $totalBudget > 0 ? min(100, round(($totalExpenses / $totalBudget) * 100)) : 0;
                
                echo json_encode(['status' => 'success', 'data' => [
                    'total_expenses' => (float)$totalExpenses,
                    'pending_amount' => (float)($pending['amount'] ?? 0),
                    'pending_count' => (int)($pending['count'] ?? 0),
                    'remaining_budget' => (float)$remainingBudget,
                    'budget_percent' => $budgetPercent
                ]]);
            }
            elseif ($action === 'charts') {
                // Category breakdown
                $stmt = $pdo->prepare("
                    SELECT vc.category_name, SUM(e.actual_cost) as total 
                    FROM expenses e 
                    LEFT JOIN vendor_categories vc ON e.category_id = vc.id 
                    WHERE e.event_id = ? AND e.actual_cost > 0
                    GROUP BY e.category_id
                ");
                $stmt->execute([$eventId]);
                $donutData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Monthly forecast (Simplified based on date_logged)
                $stmt = $pdo->prepare("
                    SELECT DATE_FORMAT(date_logged, '%b') as month, SUM(actual_cost) as actual, SUM(estimated_cost) as projected 
                    FROM expenses 
                    WHERE event_id = ? AND date_logged >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(date_logged, '%Y-%m')
                    ORDER BY date_logged ASC
                ");
                $stmt->execute([$eventId]);
                $barData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'data' => [
                    'donut' => $donutData,
                    'bar' => $barData
                ]]);
            }
            else {
                // List expenses with filters
                $catFilter = $_GET['category'] ?? 'all';
                $statusFilter = $_GET['status'] ?? 'all';
                
                $sql = "SELECT e.*, vc.category_name FROM expenses e LEFT JOIN vendor_categories vc ON e.category_id = vc.id WHERE e.event_id = ?";
                $params = [$eventId];
                
                if ($catFilter !== 'all' && is_numeric($catFilter)) {
                    $sql .= " AND e.category_id = ?";
                    $params[] = $catFilter;
                }
                
                if ($statusFilter !== 'all') {
                    $sql .= " AND e.payment_status = ?";
                    $params[] = $statusFilter;
                }
                
                $sql .= " ORDER BY e.date_logged DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'data' => $expenses]);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            try {
                if (empty($input['id'])) {
                    // Create
                    $stmt = $pdo->prepare("INSERT INTO expenses (event_id, category_id, vendor_item_name, estimated_cost, actual_cost, payment_status, date_logged, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $eventId,
                        $input['category_id'] ?? null,
                        $input['vendor_item_name'],
                        $input['estimated_cost'] ?? 0,
                        $input['actual_cost'] ?? 0,
                        $input['payment_status'] ?? 'Pending',
                        $input['date_logged'] ?? date('Y-m-d'),
                        $input['notes'] ?? ''
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Expense added']);
                } else {
                    // Update
                    $stmt = $pdo->prepare("UPDATE expenses SET category_id=?, vendor_item_name=?, estimated_cost=?, actual_cost=?, payment_status=?, date_logged=?, notes=? WHERE id=? AND event_id=?");
                    $stmt->execute([
                        $input['category_id'] ?? null,
                        $input['vendor_item_name'],
                        $input['estimated_cost'] ?? 0,
                        $input['actual_cost'] ?? 0,
                        $input['payment_status'] ?? 'Pending',
                        $input['date_logged'] ?? date('Y-m-d'),
                        $input['notes'] ?? '',
                        $input['id'],
                        $eventId
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Expense updated']);
                }
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND event_id = ?");
                $stmt->execute([$id, $eventId]);
                echo json_encode(['status' => 'success', 'message' => 'Expense deleted']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
