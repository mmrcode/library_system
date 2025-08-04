<?php
/**
 * AJAX Search Users Endpoint
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
$activeOnly = isset($input['active_only']) ? $input['active_only'] : false;

if (strlen($query) < 2) {
    echo json_encode(['users' => []]);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Build search query
    $whereClause = "WHERE u.user_type != 'admin' AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.registration_number LIKE ?)";
    $params = ["%$query%", "%$query%", "%$query%", "%$query%"];
    
    if ($activeOnly) {
        $whereClause .= " AND u.status = 'active'";
    }
    
    $sql = "SELECT u.user_id, u.username, u.full_name, u.email, u.registration_number, u.department, u.user_type, u.status,
                   COUNT(CASE WHEN bi.status = 'issued' THEN 1 END) as active_issues
            FROM users u 
            LEFT JOIN book_issues bi ON u.user_id = bi.user_id
            $whereClause 
            GROUP BY u.user_id
            ORDER BY u.full_name ASC 
            LIMIT 10";
    
    $users = $db->fetchAll($sql, $params);
    
    echo json_encode(['users' => $users]);
    
} catch (Exception $e) {
    error_log("Error searching users: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
?>
