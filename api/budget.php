<?php
// api/budget.php
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
$userId = $_SESSION['user_id'];

// Get active event for user
$stmt = $pdo->prepare("SELECT id, event_name, total_budget FROM events WHERE user_id = ? ORDER BY event_date ASC LIMIT 1");
$stmt->execute([$userId]);
$activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activeEvent) {
    echo json_encode(['status' => 'error', 'message' => 'No active events found.']);
    exit;
}

$eventId = $activeEvent['id'];
$eventName = $activeEvent['event_name'];
$totalBudget = (float)($activeEvent['total_budget'] ?: 0);

if ($method === 'GET') {
    try {
        // 1. Total Spent
        $stmt = $pdo->prepare("SELECT SUM(actual_cost) FROM expenses WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $totalSpent = (float)($stmt->fetchColumn() ?: 0);

        // 2. Spent Last Month (for +12% badge)
        $stmt = $pdo->prepare("SELECT SUM(actual_cost) FROM expenses WHERE event_id = ? AND date_logged >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");
        $stmt->execute([$eventId]);
        $spentLastMonth = (float)($stmt->fetchColumn() ?: 0);
        $velocityText = $spentLastMonth > ($totalBudget * 0.2) ? 'High Velocity' : '';

        $remainingBudget = max(0, $totalBudget - $totalSpent);
        $healthPct = $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100)) : 0;

        // 3. Category Breakdown (Donut) & Actual vs Estimated (Bars)
        $stmt = $pdo->prepare("
            SELECT 
                vc.category_name, 
                vc.allocated_amount as estimated,
                COALESCE(SUM(e.actual_cost), 0) AS actual
            FROM vendor_categories vc
            LEFT JOIN expenses e ON vc.id = e.category_id AND e.event_id = ?
            WHERE vc.event_id = ?
            GROUP BY vc.id
            ORDER BY actual DESC
        ");
        $stmt->execute([$eventId, $eventId]);
        $categoriesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $donutData = [];
        $barData = [];
        $alerts = [];
        
        $totalCategorySpend = 0;
        foreach ($categoriesData as $c) {
            $totalCategorySpend += $c['actual'];
        }

        foreach ($categoriesData as $c) {
            // For Donut
            if ($c['actual'] > 0) {
                $pct = $totalCategorySpend > 0 ? round(($c['actual'] / $totalCategorySpend) * 100) : 0;
                $donutData[] = [
                    'label' => $c['category_name'],
                    'value' => $c['actual'],
                    'percentage' => $pct
                ];
            }
            
            // For Bar
            $barData[] = [
                'label' => $c['category_name'],
                'estimated' => (float)$c['estimated'],
                'actual' => (float)$c['actual']
            ];

            // Alerts
            if ($c['actual'] > $c['estimated'] && $c['estimated'] > 0) {
                $over = $c['actual'] - $c['estimated'];
                $alerts[] = [
                    'title' => $c['category_name'] . ' Over-spend',
                    'message' => 'Expenses have exceeded the allocation by ' . number_format($over, 2) . '.',
                    'type' => 'critical'
                ];
            } elseif ($c['actual'] > ($c['estimated'] * 0.9) && $c['estimated'] > 0) {
                $pct = round(($c['actual'] / $c['estimated']) * 100);
                $alerts[] = [
                    'title' => $c['category_name'] . ' Limit',
                    'message' => 'Budget is at ' . $pct . '% of the projected limit.',
                    'type' => 'upcoming'
                ];
            }
        }

        // 4. Spending Trend (Last 30 Days)
        $stmt = $pdo->prepare("
            SELECT DATE(date_logged) as log_date, SUM(actual_cost) as daily_spent
            FROM expenses
            WHERE event_id = ? AND date_logged >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(date_logged)
            ORDER BY log_date ASC
        ");
        $stmt->execute([$eventId]);
        $trendRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill gaps in the last 30 days
        $trendData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $val = 0;
            foreach ($trendRows as $tr) {
                if ($tr['log_date'] === $date) {
                    $val = (float)$tr['daily_spent'];
                    break;
                }
            }
            $trendData[] = [
                'date' => date('M d', strtotime($date)),
                'amount' => $val
            ];
        }

        echo json_encode(['status' => 'success', 'data' => [
            'event_name' => $eventName,
            'metrics' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining' => $remainingBudget,
                'health_pct' => $healthPct,
                'velocity_text' => $velocityText
            ],
            'donut' => $donutData,
            'bars' => $barData,
            'trend' => $trendData,
            'alerts' => $alerts
        ]]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
