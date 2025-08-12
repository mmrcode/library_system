<?php
/**
 * Fine Data API Endpoint
 * Provides fine calculation data for reports and dashboards
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/fine_functions.php';

// quick auth check: only admins can call this API
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

try {
    // figuring out what the admin wants to do (kinda like a mode)
    // NOTE: keeping default as 'report' so the dashboard loads something useful
    $action = $_GET['action'] ?? 'report';
    
    // using a switch here because there are multiple small features behind one endpoint
    // TODO: maybe split into separate endpoints later if needed
    switch ($action) {
        case 'statistics':
            // super light one: just returns the top cards data
            $stats = getFineStatistics($pdo);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'report':
            // building filter params – simple checks only (no heavy validation here)
            // NOTE: server trusts UI inputs minimally; DB queries still parameterized in helpers
            $filters = [
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'status' => $_GET['status'] ?? 'all',
                'user_type' => $_GET['user_type'] ?? 'all'
            ];
            
            $report_data = getFineReport($pdo, $filters);
            echo json_encode([
                'success' => true,
                'data' => $report_data,
                'filters' => $filters
            ]);
            break;
            
        case 'calculate_overdue':
            // this triggers a recalculation, useful when admin clicks the "Recalculate" button on UI
            // internally uses FineCalculator->calculateAllOverdueFines()
            $calculator = new FineCalculator($pdo);
            $overdue_fines = $calculator->calculateAllOverdueFines();

            echo json_encode([
                'success' => true,
                'message' => 'Calculated fines for ' . count($overdue_fines) . ' overdue items',
                'data' => $overdue_fines
            ]);
            break;
            
        case 'send_reminders':
            // send reminder emails for users with pending fines
            // Simple approach: if no IDs sent, pick users with fines > 7 days
            $user_ids = $_POST['user_ids'] ?? [];
            if (empty($user_ids)) {
                // Get users with overdue fines > 7 days
                $stmt = $pdo->prepare("
                    SELECT DISTINCT f.user_id 
                    FROM fines f 
                    WHERE f.status IN ('pending', 'overdue') 
                    AND f.days_overdue > 7
                    AND (f.last_reminder_date IS NULL OR f.last_reminder_date < DATE_SUB(NOW(), INTERVAL 3 DAY))
                ");
                $stmt->execute();
                $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            $results = sendFineReminders($pdo, $user_ids);
            $success_count = count(array_filter($results));
            
            echo json_encode([
                'success' => true,
                'message' => "Sent reminders to {$success_count} users",
                'data' => $results
            ]);
            break;
            
        case 'payment':
            // quick guard: only allow POST for payment (safer)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $fine_id = $_POST['fine_id'] ?? null;
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $notes = $_POST['notes'] ?? '';
            
            if (!$fine_id) {
                throw new Exception('Fine ID required');
            }
            
            // NOTE: FineCalculator will also mark transactions in history table
            $calculator = new FineCalculator($pdo);
            $result = $calculator->processFinePayment($fine_id, $payment_method, $notes);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Payment processed successfully' : 'Payment processing failed'
            ]);
            break;
            
        case 'waive':
            // waive a fine (admin only); again POST-only
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $fine_id = $_POST['fine_id'] ?? null;
            $reason = $_POST['reason'] ?? '';
            
            if (!$fine_id) {
                throw new Exception('Fine ID required');
            }
            
            // Tiny reminder: waivers should include a decent reason (stored in transactions)
            $calculator = new FineCalculator($pdo);
            $result = $calculator->waiveFine($fine_id, $reason);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fine waived successfully' : 'Fine waiver failed'
            ]);
            break;
            
        case 'monthly_trends':
            // charts need data grouped by month; keeping it simple SQL-side
            // Get monthly fine collection trends for the last 12 months
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(calculated_date, '%Y-%m') as month,
                    SUM(CASE WHEN status = 'paid' THEN fine_amount ELSE 0 END) as collected,
                    SUM(CASE WHEN status IN ('pending', 'overdue') THEN fine_amount ELSE 0 END) as outstanding,
                    COUNT(*) as total_fines
                FROM fines 
                WHERE calculated_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(calculated_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute();
            $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $trends
            ]);
            break;
            
        case 'category_analysis':
            // breakdown by user type (useful for admin to see patterns)
            // Get fine analysis by user category
            $stmt = $pdo->prepare("
                SELECT 
                    u.user_type,
                    COUNT(*) as fine_count,
                    SUM(f.fine_amount) as total_amount,
                    AVG(f.fine_amount) as avg_amount,
                    AVG(f.days_overdue) as avg_days_overdue
                FROM fines f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.calculated_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY u.user_type
                ORDER BY total_amount DESC
            ");
            $stmt->execute();
            $analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // just a simple echo for now, might want to add more formatting later
            echo json_encode([
                'success' => true,
                'data' => $analysis
            ]);
            break;
            
        default:
            // invalid action, just throw an exception and let the catch block handle it
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    // return a basic error response so frontend can show a toast/alert
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
