<?php
/**
 * Process Book Requests - Admin
 * Handles AJAX requests for approving/rejecting book requests
 */

define('LIBRARY_SYSTEM', true);
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Set JSON header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'data' => []
];

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the action
    $action = $_POST['action'] ?? '';
    
    // Process based on action
    switch ($action) {
        case 'update_request_status':
            $requestId = (int)($_POST['request_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            // Validate input
            if ($requestId <= 0) {
                throw new Exception('Invalid request ID');
            }
            
            if (!in_array($status, ['approved', 'rejected', 'fulfilled', 'cancelled'])) {
                throw new Exception('Invalid status');
            }
            
            // Get database connection
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Get current user ID (admin)
            $adminId = $_SESSION['user_id'] ?? 0;
            
            // Update the request status
            $stmt = $conn->prepare("
                UPDATE book_requests 
                SET status = ?, 
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), ?),
                    processed_by = ?,
                    processed_date = NOW(),
                    updated_at = NOW()
                WHERE request_id = ?
            ");
            
            // Add reason to notes if provided
            $adminNotes = "\n" . date('Y-m-d H:i:s') . " - " . ucfirst($status);
            if (!empty($reason)) {
                $adminNotes .= ": " . $reason;
            }
            
            $stmt->bind_param("ssii", $status, $adminNotes, $adminId, $requestId);
            
            if ($stmt->execute()) {
                // If approved, create a book issue if it's an 'issue' request
                if ($status === 'approved') {
                    // Get the request details
                    $request = $db->fetchOne("
                        SELECT r.*, b.available_copies 
                        FROM book_requests r
                        JOIN books b ON r.book_id = b.book_id
                        WHERE r.request_id = ?
                    ", [$requestId]);
                    
                    if ($request && $request['request_type'] === 'issue' && $request['available_copies'] > 0) {
                        // Create book issue
                        $issueStmt = $conn->prepare("
                            INSERT INTO book_issues (
                                book_id, 
                                user_id, 
                                issue_date, 
                                due_date, 
                                status, 
                                created_by, 
                                created_at, 
                                updated_at
                            ) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'issued', ?, NOW(), NOW())
                        ");
                        
                        $duration = (int)$request['requested_duration'] ?: 14;
                        $issueStmt->bind_param("iiii", 
                            $request['book_id'], 
                            $request['user_id'], 
                            $duration,
                            $adminId
                        );
                        
                        if ($issueStmt->execute()) {
                            // Update book available copies
                            $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = " . $request['book_id']);
                            
                            // Update request to fulfilled
                            $conn->query("UPDATE book_requests SET status = 'fulfilled' WHERE request_id = $requestId");
                            
                            $response['message'] = 'Book request approved and issued successfully';
                        } else {
                            throw new Exception('Failed to create book issue: ' . $conn->error);
                        }
                    } else {
                        $response['message'] = 'Book request approved successfully';
                    }
                } else {
                    $response['message'] = 'Book request ' . $status . ' successfully';
                }
                
                $response['success'] = true;
            } else {
                throw new Exception('Failed to update request status: ' . $conn->error);
            }
            
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Return JSON response
echo json_encode($response);
?>
