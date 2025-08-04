<?php
/**
 * Check Overdue Books - Student AJAX
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
    
    // Get overdue books count
    $overdueCount = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM book_issues 
        WHERE user_id = ? AND status = 'overdue'
    ", [$currentUser['user_id']]) ?? 0;
    
    // Get books due soon (within 3 days)
    $dueSoonCount = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM book_issues 
        WHERE user_id = ? AND status = 'issued' 
        AND DATEDIFF(due_date, CURDATE()) BETWEEN 0 AND 3
    ", [$currentUser['user_id']]) ?? 0;
    
    // Get pending fines
    $pendingFines = $db->fetchColumn("
        SELECT COALESCE(SUM(fine_amount), 0) 
        FROM fines 
        WHERE user_id = ? AND status = 'pending'
    ", [$currentUser['user_id']]) ?? 0;
    
    echo json_encode([
        'success' => true,
        'overdue_count' => $overdueCount,
        'due_soon_count' => $dueSoonCount,
        'pending_fines' => $pendingFines,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check overdue books',
        'message' => $e->getMessage()
    ]);
}
?> 