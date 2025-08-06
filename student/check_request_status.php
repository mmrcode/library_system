<?php
/**
 * Check Book Request Status - Library Management System
 * AJAX endpoint to check if user has pending request for a book
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

define('LIBRARY_SYSTEM', true);

// Include required files
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/request_functions.php';

// Require student access
requireStudent();

// Set JSON header
header('Content-Type: application/json');

$currentUser = getCurrentUser();
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

if ($book_id <= 0) {
    echo json_encode(['error' => 'Invalid book ID']);
    exit;
}

// Check if user has pending request for this book
$existing_request = $requestSystem->getUserBookRequest($currentUser['user_id'], $book_id);

$response = [
    'has_pending_request' => false,
    'request_status' => null,
    'request_date' => null
];

if ($existing_request && in_array($existing_request['status'], ['pending', 'approved'])) {
    $response['has_pending_request'] = true;
    $response['request_status'] = $existing_request['status'];
    $response['request_date'] = $existing_request['request_date'];
}

echo json_encode($response);
?>
