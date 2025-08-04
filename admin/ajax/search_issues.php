<?php
/**
 * AJAX Search Issues Endpoint
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

define('LIBRARY_SYSTEM', true);

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Require admin access
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['query'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Verify CSRF token
if (!verifyCsrfToken($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit();
}

$query = sanitizeInput($input['query']);
$status = isset($input['status']) ? sanitizeInput($input['status']) : 'all';

if (strlen($query) < 2) {
    echo json_encode(['issues' => []]);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Build search query
    $whereClause = "WHERE (b.title LIKE ? OR b.author LIKE ? OR u.full_name LIKE ? OR u.registration_number LIKE ?)";
    $params = ["%$query%", "%$query%", "%$query%", "%$query%"];
    
    if ($status === 'active') {
        $whereClause .= " AND bi.status IN ('issued', 'overdue')";
    } elseif ($status !== 'all') {
        $whereClause .= " AND bi.status = ?";
        $params[] = $status;
    }
    
    $sql = "SELECT bi.issue_id, bi.issue_date, bi.due_date, bi.status,
                   b.title, b.author,
                   u.full_name, u.registration_number,
                   DATEDIFF(CURDATE(), bi.due_date) as days_overdue
            FROM book_issues bi
            JOIN books b ON bi.book_id = b.book_id
            JOIN users u ON bi.user_id = u.user_id
            $whereClause 
            ORDER BY bi.due_date ASC 
            LIMIT 10";
    
    $issues = $db->fetchAll($sql, $params);
    
    // Format dates for display
    foreach ($issues as &$issue) {
        $issue['issue_date'] = formatDate($issue['issue_date']);
        $issue['due_date'] = formatDate($issue['due_date']);
        $issue['days_overdue'] = max(0, $issue['days_overdue']);
    }
    
    echo json_encode(['issues' => $issues]);
    
} catch (Exception $e) {
    error_log("Error searching issues: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
?>
