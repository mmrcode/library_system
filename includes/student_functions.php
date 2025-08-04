<?php
/**
 * Student Functions - Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

/**
 * Get overdue books count for a student (student-specific version)
 */
function getStudentOverdueBooksCount($userId) {
    $db = Database::getInstance();
    return $db->fetchColumn("
        SELECT COUNT(*) 
        FROM book_issues 
        WHERE user_id = ? AND status = 'overdue'
    ", [$userId]) ?? 0;
}

/**
 * Get books due soon (within 3 days)
 */
function getStudentBooksDueSoon($userId) {
    $db = Database::getInstance();
    return $db->fetchAll("
        SELECT bi.*, b.title, b.author, b.isbn,
               DATEDIFF(bi.due_date, CURDATE()) as days_remaining
        FROM book_issues bi
        JOIN books b ON bi.book_id = b.book_id
        WHERE bi.user_id = ? AND bi.status = 'issued' 
        AND DATEDIFF(bi.due_date, CURDATE()) BETWEEN 0 AND 3
        ORDER BY bi.due_date ASC
    ", [$userId]);
}

/**
 * Get student's reading statistics
 */
function getStudentReadingStats($userId) {
    $db = Database::getInstance();
    
    $stats = [
        'total_books' => 0,
        'currently_issued' => 0,
        'overdue_books' => 0,
        'total_fines' => 0,
        'favorite_category' => null,
        'reading_streak' => 0
    ];
    
    // Total books issued
    $stats['total_books'] = $db->fetchColumn("
        SELECT COUNT(*) FROM book_issues WHERE user_id = ?
    ", [$userId]) ?? 0;
    
    // Currently issued
    $stats['currently_issued'] = $db->fetchColumn("
        SELECT COUNT(*) FROM book_issues 
        WHERE user_id = ? AND status IN ('issued', 'overdue')
    ", [$userId]) ?? 0;
    
    // Overdue books
    $stats['overdue_books'] = $db->fetchColumn("
        SELECT COUNT(*) FROM book_issues 
        WHERE user_id = ? AND status = 'overdue'
    ", [$userId]) ?? 0;
    
    // Total fines
    $stats['total_fines'] = $db->fetchColumn("
        SELECT COALESCE(SUM(fine_amount), 0) FROM fines 
        WHERE user_id = ? AND status = 'pending'
    ", [$userId]) ?? 0;
    
    // Favorite category
    $favoriteCategory = $db->fetchOne("
        SELECT c.category_name, COUNT(*) as book_count
        FROM book_issues bi
        JOIN books b ON bi.book_id = b.book_id
        LEFT JOIN categories c ON b.category_id = c.category_id
        WHERE bi.user_id = ? AND c.category_id IS NOT NULL
        GROUP BY c.category_id, c.category_name
        ORDER BY book_count DESC
        LIMIT 1
    ", [$userId]);
    
    if ($favoriteCategory) {
        $stats['favorite_category'] = $favoriteCategory['category_name'];
    }
    
    return $stats;
}

/**
 * Get recommended books for a student
 */
function getStudentRecommendedBooks($userId, $limit = 6) {
    $db = Database::getInstance();
    
    // Get books based on user's reading history
    $recommendedBooks = $db->fetchAll("
        SELECT DISTINCT b.*, c.category_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        WHERE b.status = 'active' AND b.available_copies > 0
        AND b.category_id IN (
            SELECT DISTINCT b2.category_id 
            FROM book_issues bi2 
            JOIN books b2 ON bi2.book_id = b2.book_id 
            WHERE bi2.user_id = ? AND b2.category_id IS NOT NULL
        )
        AND b.book_id NOT IN (
            SELECT bi.book_id FROM book_issues bi WHERE bi.user_id = ?
        )
        ORDER BY b.created_at DESC
        LIMIT ?
    ", [$userId, $userId, $limit]);
    
    // If not enough recommendations, add popular books
    if (count($recommendedBooks) < $limit) {
        $remaining = $limit - count($recommendedBooks);
        $popularBooks = $db->fetchAll("
            SELECT b.*, c.category_name
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.category_id
            WHERE b.status = 'active' AND b.available_copies > 0
            AND b.book_id NOT IN (
                SELECT bi.book_id FROM book_issues bi WHERE bi.user_id = ?
            )
            AND b.book_id NOT IN (
                SELECT bi2.book_id FROM book_issues bi2 
                JOIN books b2 ON bi2.book_id = b2.book_id 
                WHERE bi2.user_id = ? AND b2.category_id IS NOT NULL
            )
            ORDER BY b.created_at DESC
            LIMIT ?
        ", [$userId, $userId, $remaining]);
        
        $recommendedBooks = array_merge($recommendedBooks, $popularBooks);
    }
    
    return array_slice($recommendedBooks, 0, $limit);
}

/**
 * Get student's recent activity
 */
function getStudentRecentActivity($userId, $limit = 10) {
    $db = Database::getInstance();
    
    return $db->fetchAll("
        SELECT bi.*, b.title, b.author, b.isbn,
               CASE 
                   WHEN bi.status = 'returned' THEN 'Returned'
                   WHEN bi.status = 'issued' THEN 'Issued'
                   WHEN bi.status = 'overdue' THEN 'Overdue'
               END as activity_type
        FROM book_issues bi
        JOIN books b ON bi.book_id = b.book_id
        WHERE bi.user_id = ?
        ORDER BY bi.created_at DESC
        LIMIT ?
    ", [$userId, $limit]);
}

/**
 * Calculate fine for overdue book (student-specific version)
 */
function calculateStudentFine($issueDate, $dueDate, $finePerDay = FINE_PER_DAY) {
    $due = new DateTime($dueDate);
    $today = new DateTime();
    
    if ($today > $due) {
        $interval = $today->diff($due);
        $daysOverdue = $interval->days;
        return $daysOverdue * $finePerDay;
    }
    
    return 0;
}

/**
 * Check if student can borrow more books
 */
function canStudentBorrowMoreBooks($userId, $maxBooks = MAX_BOOKS_PER_USER) {
    $db = Database::getInstance();
    
    $currentBooks = $db->fetchColumn("
        SELECT COUNT(*) FROM book_issues 
        WHERE user_id = ? AND status IN ('issued', 'overdue')
    ", [$userId]) ?? 0;
    
    return $currentBooks < $maxBooks;
}


?> 