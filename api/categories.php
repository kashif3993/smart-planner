<?php
// api/categories.php
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

// Get active event for user
$stmt = $pdo->prepare("SELECT id, total_budget FROM events WHERE user_id = ? ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$userId]);
$activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activeEvent) {
    echo json_encode(['status' => 'error', 'message' => 'No active events found.']);
    exit;
}

$eventId = $activeEvent['id'];
$totalEventBudget = $activeEvent['total_budget'] ?: 0;

switch ($method) {
    case 'GET':
        try {
            if ($action === 'metrics') {
                // For "Budget Allocation Mix" stacked bar chart
                $stmt = $pdo->prepare("
                    SELECT 
                        vc.category_name, 
                        vc.allocated_amount,
                        COALESCE(SUM(e.actual_cost), 0) AS total_spent
                    FROM vendor_categories vc
                    LEFT JOIN expenses e ON vc.id = e.category_id AND e.event_id = ?
                    WHERE vc.event_id = ?
                    GROUP BY vc.id
                    ORDER BY vc.category_name ASC
                ");
                $stmt->execute([$eventId, $eventId]);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Smart insights calculations
                $insights = [];
                $totalAllocated = 0;
                $totalSpentAll = 0;

                foreach ($categories as $cat) {
                    $totalAllocated += $cat['allocated_amount'];
                    $totalSpentAll += $cat['total_spent'];

                    if ($cat['total_spent'] > $cat['allocated_amount']) {
                        $over = $cat['total_spent'] - $cat['allocated_amount'];
                        $pct = $cat['allocated_amount'] > 0 ? round(($over / $cat['allocated_amount']) * 100) : 100;
                        $insights[] = [
                            'type' => 'overflow',
                            'title' => $cat['category_name'] . ' Overflow',
                            'message' => $cat['category_name'] . ' has exceeded budget by ' . $pct . '%.',
                            'icon' => 'warning'
                        ];
                    } elseif ($cat['allocated_amount'] > 0 && $cat['total_spent'] > 0 && $cat['total_spent'] < $cat['allocated_amount'] * 0.5) {
                         $insights[] = [
                            'type' => 'savings',
                            'title' => 'Savings Opportunity',
                            'message' => $cat['category_name'] . ' spend is well below allocation.',
                            'icon' => 'success'
                        ];
                    }
                }

                // Add forecast insight
                $forecastPct = $totalEventBudget > 0 ? round(($totalSpentAll / $totalEventBudget) * 100) : 0;
                $insights[] = [
                    'type' => 'forecast',
                    'title' => 'Forecast',
                    'message' => 'Projected total event spend is on track for ' . $forecastPct . '% of total budget.',
                    'icon' => 'info'
                ];

                echo json_encode(['status' => 'success', 'data' => [
                    'chart' => $categories,
                    'insights' => $insights
                ]]);
            } else {
                // List Action: get all categories with spent amounts
                $stmt = $pdo->prepare("
                    SELECT 
                        vc.id, 
                        vc.category_name, 
                        vc.suggested_percentage, 
                        vc.allocated_amount, 
                        vc.notes,
                        COALESCE(SUM(e.actual_cost), 0) AS total_spent
                    FROM vendor_categories vc
                    LEFT JOIN expenses e ON vc.id = e.category_id AND e.event_id = ?
                    WHERE vc.event_id = ?
                    GROUP BY vc.id
                    ORDER BY vc.id ASC
                ");
                $stmt->execute([$eventId, $eventId]);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Add derived fields
                foreach ($categories as &$cat) {
                    $cat['remaining'] = $cat['allocated_amount'] - $cat['total_spent'];
                    $cat['utilization'] = $cat['allocated_amount'] > 0 
                        ? round(($cat['total_spent'] / $cat['allocated_amount']) * 100, 1) 
                        : ($cat['total_spent'] > 0 ? 100 : 0);
                }

                echo json_encode(['status' => 'success', 'data' => $categories]);
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
                    $stmt = $pdo->prepare("INSERT INTO vendor_categories (event_id, category_name, suggested_percentage, allocated_amount, notes) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $eventId,
                        $input['category_name'],
                        $input['suggested_percentage'] ?? 0,
                        $input['allocated_amount'] ?? 0,
                        $input['notes'] ?? ''
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Category added']);
                } else {
                    // Update
                    $stmt = $pdo->prepare("UPDATE vendor_categories SET category_name=?, suggested_percentage=?, allocated_amount=?, notes=? WHERE id=? AND event_id=?");
                    $stmt->execute([
                        $input['category_name'],
                        $input['suggested_percentage'] ?? 0,
                        $input['allocated_amount'] ?? 0,
                        $input['notes'] ?? '',
                        $input['id'],
                        $eventId
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Category updated']);
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
                $stmt = $pdo->prepare("DELETE FROM vendor_categories WHERE id = ? AND event_id = ?");
                $stmt->execute([$id, $eventId]);
                echo json_encode(['status' => 'success', 'message' => 'Category deleted']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
