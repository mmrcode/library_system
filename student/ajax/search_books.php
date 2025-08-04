<?php
/**
 * Search Books - Student AJAX
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
require_once '../../includes/thumbnail_generator.php';

// Require student access
requireStudent();

// Set JSON header
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Get search parameters
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $author = $_GET['author'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereConditions = ["b.status = 'active'"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $whereConditions[] = "c.category_id = ?";
        $params[] = $category;
    }
    
    if (!empty($author)) {
        $whereConditions[] = "b.author LIKE ?";
        $params[] = "%$author%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.category_id 
        WHERE $whereClause
    ";
    $totalBooks = $db->fetchColumn($countQuery, $params) ?? 0;
    
    // Get books
    $query = "
        SELECT b.*, c.category_name,
               CASE 
                   WHEN b.available_copies > 0 THEN 'Available'
                   ELSE 'Not Available'
               END as availability_status
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        WHERE $whereClause
        ORDER BY b.title ASC
        LIMIT $limit OFFSET $offset
    ";
    
    $books = $db->fetchAll($query, $params);
    
    // Get categories for filter
    $categories = $db->fetchAll("SELECT category_id, category_name FROM categories ORDER BY category_name");
    
    echo json_encode([
        'success' => true,
        'books' => $books,
        'categories' => $categories,
        'total' => $totalBooks,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalBooks / $limit)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to search books',
        'message' => $e->getMessage()
    ]);
}
?> 