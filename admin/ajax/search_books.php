<?php
/**
 * AJAX Search Books Endpoint
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
$availableOnly = isset($input['available_only']) ? $input['available_only'] : false;

if (strlen($query) < 2) {
    echo json_encode(['books' => []]);
    exit();
}

try {
    $db = Database::getInstance();
    
    // Build search query
    $whereClause = "WHERE b.status = 'active' AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params = ["%$query%", "%$query%", "%$query%"];
    
    if ($availableOnly) {
        $whereClause .= " AND b.available_copies > 0";
    }
    
    $sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.available_copies, b.total_copies, c.category_name
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.category_id 
            $whereClause 
            ORDER BY b.title ASC 
            LIMIT 10";
    
    $books = $db->fetchAll($sql, $params);
    
    echo json_encode(['books' => $books]);
    
} catch (Exception $e) {
    error_log("Error searching books: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
?>
