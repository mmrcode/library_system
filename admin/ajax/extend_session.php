<?php
/**
 * Extend Session - AJAX
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

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'error' => 'User not logged in'
        ]);
        exit;
    }
    
    // Extend session by updating last activity
    $currentUser = getCurrentUser();
    $db = Database::getInstance();
    
    // Update last activity in database
    $db->execute("UPDATE users SET last_activity = NOW() WHERE user_id = ?", [$currentUser['user_id']]);
    
    // Regenerate session ID for security
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Session extended successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to extend session',
        'message' => $e->getMessage()
    ]);
}
?> 