<?php
/**
 * Book Request Functions for Library Management System
 * Handles book request queue and processing
 * Created by: Mohammad Muqsit Raja (BCA22739)
 */

require_once 'config.php';
require_once 'database.php';
require_once 'email_functions.php';

class BookRequestSystem {
    private $db;
    private $emailSystem;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // only boot email system if the tables are actually there (fresh setups won't have them yet)
        try {
            if ($this->tableExists('system_settings') && $this->tableExists('email_logs')) {
                $this->emailSystem = new EmailNotificationSystem();
            } else {
                $this->emailSystem = null;
            }
        } catch (Exception $e) {
            $this->emailSystem = null;
        }
    }
    
    /**
     * Tiny helper to check if a table exists in current DB.
     * NOTE: using a quick SHOW TABLES which is fine for admin-side ops
     */
    private function tableExists($tableName) {
        try {
            $result = $this->db->query("SHOW TABLES LIKE '{$tableName}'");
            return ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create a new book request
     */
    public function createBookRequest($book_id, $user_id, $request_type = 'issue', $priority = 'normal', $requested_duration = 14, $notes = '') {
        // Check if book_requests table exists
        if (!$this->tableExists('book_requests')) {
            return [
                'success' => false,
                'message' => 'Book request system is not yet installed. Please contact the administrator.'
            ];
        }
        
        try {
            // simple guard: cap pending requests per user (configurable)
            $max_requests = $this->getSystemSetting('max_pending_requests', 5);
            $pending_count = $this->getUserPendingRequestsCount($user_id);
            
            if ($pending_count >= $max_requests) {
                return [
                    'success' => false,
                    'message' => "You have reached the maximum limit of {$max_requests} pending requests."
                ];
            }
            
            // avoid duplicates: if already pending/approved for same book, block new one
            $existing_request = $this->getUserBookRequest($user_id, $book_id);
            if ($existing_request && in_array($existing_request['status'], ['pending', 'approved'])) {
                return [
                    'success' => false,
                    'message' => 'You already have a pending request for this book.'
                ];
            }
            
            // Create the request
            $stmt = $this->db->prepare("
                INSERT INTO book_requests (book_id, user_id, request_type, priority, requested_duration, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissss", $book_id, $user_id, $request_type, $priority, $requested_duration, $notes);
            $stmt->execute();
            $request_id = $this->db->insert_id;
            
            // Create notification for user
            $this->createNotification(
                $user_id,
                'Book Request Submitted',
                'Your book request has been submitted successfully and is pending librarian approval.',
                'book_request',
                $request_id
            );
            
            // Send email notification to librarian (if email system is available)
            if ($this->emailSystem) {
                $this->emailSystem->sendBookRequestNotification($request_id);
                
                // Update email sent flag
                $stmt = $this->db->prepare("UPDATE book_requests SET email_sent = 'yes' WHERE request_id = ?");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
            }
            
            return [
                'success' => true,
                'message' => 'Book request submitted successfully! You will be notified when the librarian processes your request.',
                'request_id' => $request_id
            ];
            
        } catch (Exception $e) {
            error_log("Error creating book request: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while submitting your request. Please try again.'
            ];
        }
    }
    
    /**
     * Process a book request (approve/reject/fulfilled)
     * TODO: maybe move inventory updates here later if auto-issue flow is added
     */
    public function processBookRequest($request_id, $status, $admin_id, $admin_notes = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE book_requests 
                SET status = ?, processed_by = ?, processed_date = NOW(), admin_notes = ?
                WHERE request_id = ?
            ");
            $stmt->bind_param("sisi", $status, $admin_id, $admin_notes, $request_id);
            $stmt->execute();
            
            // Get request details
            $request = $this->getBookRequest($request_id);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found.'];
            }
            
            // Create notification for student
            $status_messages = [
                'approved' => 'Your book request has been approved! Please visit the library to collect your book.',
                'rejected' => 'Your book request has been rejected. Please contact the librarian for more information.',
                'fulfilled' => 'Your book has been issued successfully!',
                'cancelled' => 'Your book request has been cancelled.'
            ];
            
            $this->createNotification(
                $request['user_id'],
                'Book Request ' . ucfirst($status),
                $status_messages[$status] . (!empty($admin_notes) ? "\n\nLibrarian Notes: " . $admin_notes : ''),
                'book_request',
                $request_id
            );
            
            // Send email notification to student (if email system is available)
            if ($this->emailSystem) {
                $this->emailSystem->sendRequestStatusUpdate($request_id, $status);
            }
            
            return [
                'success' => true,
                'message' => 'Request ' . $status . ' successfully!'
            ];
            
        } catch (PDOException $e) {
            error_log("Error processing book request: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing the request.'
            ];
        }
    }
    
    /**
     * Get user's pending requests count
     */
    public function getUserPendingRequestsCount($user_id) {
        if (!$this->tableExists('book_requests')) {
            return 0;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM book_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return $row[0];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get user's book request for a specific book
     */
    public function getUserBookRequest($user_id, $book_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM book_requests 
                WHERE user_id = ? AND book_id = ? 
                ORDER BY request_date DESC 
                LIMIT 1
            ");
            $stmt->execute([$user_id, $book_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get book request details
     */
    public function getBookRequest($request_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT br.*, b.title, b.author, b.isbn, u.full_name, u.email, u.registration_number
                FROM book_requests br
                JOIN books b ON br.book_id = b.book_id
                JOIN users u ON br.user_id = u.user_id
                WHERE br.request_id = ?
            ");
            $stmt->execute([$request_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get user's book requests
     */
    public function getUserBookRequests($user_id, $status = null, $limit = 50) {
        try {
            $sql = "
                SELECT br.*, b.title, b.author, b.isbn, b.book_image
                FROM book_requests br
                JOIN books b ON br.book_id = b.book_id
                WHERE br.user_id = ?
            ";
            $params = [$user_id];
            
            if ($status) {
                $sql .= " AND br.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY br.request_date DESC";
            
            // Only add LIMIT if it's not a view all request (indicated by very high limit)
            if ($limit < 1000) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
            }
            
            // Use the class's database connection
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        } catch (Exception $e) {
            error_log("Error in getUserBookRequests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all book requests for admin
     */
    public function getAllBookRequests($status = null, $limit = 100) {
        try {
            $sql = "
                SELECT br.*, b.title, b.author, b.isbn, u.full_name, u.registration_number, u.email
                FROM book_requests br
                JOIN books b ON br.book_id = b.book_id
                JOIN users u ON br.user_id = u.user_id
            ";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE br.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY br.request_date DESC LIMIT ?";
            $params[] = $limit;
            
            // Use the class's database connection
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        } catch (Exception $e) {
            error_log("Error in getAllBookRequests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a notification
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $related_request_id = null, $related_issue_id = null, $action_url = null, $action_text = null) {
        if (!$this->tableExists('notifications')) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, related_request_id, related_issue_id, action_url, action_text)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssiiis", $user_id, $title, $message, $type, $related_request_id, $related_issue_id, $action_url, $action_text);
            $stmt->execute();
            return $this->db->insert_id;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $unread_only = false, $limit = 20) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            $params = [$user_id];
            
            if ($unread_only) {
                $sql .= " AND is_read = 'no'";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 'yes' WHERE notification_id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get system setting
     */
    private function getSystemSetting($key, $default = null) {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }
    
    /**
     * Get request statistics
     */
    public function getRequestStatistics() {
        try {
            $stats = [];
            
            // Total requests
            $stmt = $this->db->query("SELECT COUNT(*) FROM book_requests");
            $stats['total_requests'] = $stmt->fetchColumn();
            
            // Pending requests
            $stmt = $this->db->query("SELECT COUNT(*) FROM book_requests WHERE status = 'pending'");
            $stats['pending_requests'] = $stmt->fetchColumn();
            
            // Approved requests
            $stmt = $this->db->query("SELECT COUNT(*) FROM book_requests WHERE status = 'approved'");
            $stats['approved_requests'] = $stmt->fetchColumn();
            
            // Rejected requests
            $stmt = $this->db->query("SELECT COUNT(*) FROM book_requests WHERE status = 'rejected'");
            $stats['rejected_requests'] = $stmt->fetchColumn();
            
            // Today's requests
            $stmt = $this->db->query("SELECT COUNT(*) FROM book_requests WHERE DATE(request_date) = CURDATE()");
            $stats['today_requests'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_requests' => 0,
                'pending_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'today_requests' => 0
            ];
        }
    }
}

// Initialize request system
$requestSystem = new BookRequestSystem();
?>
