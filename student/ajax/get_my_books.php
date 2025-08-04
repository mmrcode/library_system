<?php
/**
 * Get My Books - Student AJAX
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

// Require student access
requireStudent();

// Set JSON header
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    // Get parameters
    $status = $_GET['status'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereConditions = ["bi.user_id = ?"];
    $params = [$currentUser['user_id']];
    
    if (!empty($status)) {
        $whereConditions[] = "bi.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) 
        FROM book_issues bi
        JOIN books b ON bi.book_id = b.book_id
        WHERE $whereClause
    ";
    $totalBooks = $db->fetchColumn($countQuery, $params) ?? 0;
    
    // Get books
    $query = "
        SELECT bi.*, b.title, b.author, b.isbn, c.category_name,
               DATEDIFF(bi.due_date, CURDATE()) as days_remaining,
               CASE 
                   WHEN bi.status = 'overdue' THEN 'Overdue'
                   WHEN bi.status = 'issued' AND DATEDIFF(bi.due_date, CURDATE()) <= 3 THEN 'Due Soon'
                   WHEN bi.status = 'issued' THEN 'Active'
                   WHEN bi.status = 'returned' THEN 'Returned'
                   ELSE bi.status
               END as display_status
        FROM book_issues bi
        JOIN books b ON bi.book_id = b.book_id
        LEFT JOIN categories c ON b.category_id = c.category_id
        WHERE $whereClause
        ORDER BY bi.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $books = $db->fetchAll($query, $params);
    
    // Calculate fines for overdue books
    foreach ($books as &$book) {
        if ($book['status'] === 'overdue') {
            $daysOverdue = abs($book['days_remaining']);
            $book['fine_amount'] = $daysOverdue * FINE_PER_DAY;
        } else {
            $book['fine_amount'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'books' => $books,
        'total' => $totalBooks,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalBooks / $limit)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get my books',
        'message' => $e->getMessage()
    ]);
}
?> 