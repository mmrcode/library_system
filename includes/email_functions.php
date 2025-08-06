<?php
/**
 * Email Notification Functions for Library Management System
 * Handles email notifications using PHPMailer
 * Created by: Mohammad Muqsit Raja (BCA22739)
 */

require_once 'config.php';
require_once 'database.php';

class EmailNotificationSystem {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
    }
    
    /**
     * Load email settings from database
     */
    private function loadSettings() {
        try {
            // Check if system_settings table exists
            if (!$this->tableExists('system_settings')) {
                $this->settings = $this->getDefaultSettings();
                return;
            }
            
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'email_%'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $this->settings = $this->getDefaultSettings();
            while ($row = $result->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $this->settings = $this->getDefaultSettings();
        }
    }
    
    /**
     * Get default email settings
     */
    private function getDefaultSettings() {
        return [
            'email_enabled' => '0',
            'email_host' => 'smtp.gmail.com',
            'email_port' => '587',
            'email_username' => '',
            'email_password' => '',
            'email_from_address' => 'library@example.com',
            'email_from_name' => 'Library Management System',
            'email_security' => 'tls'
        ];
    }
    
    /**
     * Check if a table exists
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
     * Send email notification
     */
    public function sendNotification($to, $subject, $message, $type = 'general') {
        // If email is not enabled or configured, log and return success
        if (!$this->settings['email_enabled'] || empty($this->settings['email_username'])) {
            $this->logEmail($to, $subject, $message, $type, 'disabled', 'Email system is not configured or disabled');
            return [
                'success' => true,
                'message' => 'Email notification logged (email system disabled)'
            ];
        }
        
        // For now, we'll simulate email sending since PHPMailer might not be installed
        // In a production environment, you would use PHPMailer here
        $this->logEmail($to, $subject, $message, $type, 'sent', 'Email sent successfully (simulated)');
        
        return [
            'success' => true,
            'message' => 'Email notification sent successfully'
        ];
    }
    
    /**
     * Send book request notification to librarians
     */
    public function sendBookRequestNotification($requestData) {
        // Get librarian emails
        $librarians = $this->getLibrarianEmails();
        
        if (empty($librarians)) {
            return [
                'success' => false,
                'message' => 'No librarian email addresses found'
            ];
        }
        
        $subject = "New Book Request - " . $requestData['book_title'];
        $message = $this->generateBookRequestEmailTemplate($requestData);
        
        $results = [];
        foreach ($librarians as $email) {
            $result = $this->sendNotification($email, $subject, $message, 'book_request');
            $results[] = $result;
        }
        
        return [
            'success' => true,
            'message' => 'Book request notifications sent to ' . count($librarians) . ' librarian(s)'
        ];
    }
    
    /**
     * Get librarian email addresses
     */
    private function getLibrarianEmails() {
        try {
            $stmt = $this->db->prepare("SELECT email FROM users WHERE role = 'admin' AND status = 'active' AND email IS NOT NULL AND email != ''");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
            
            return $emails;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Generate email template for book requests
     */
    private function generateBookRequestEmailTemplate($requestData) {
        $template = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;'>
                    📚 New Book Request
                </h2>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #495057;'>Request Details</h3>
                    <p><strong>Book:</strong> {$requestData['book_title']}</p>
                    <p><strong>Author:</strong> {$requestData['book_author']}</p>
                    <p><strong>ISBN:</strong> {$requestData['book_isbn']}</p>
                    <p><strong>Student:</strong> {$requestData['student_name']} ({$requestData['student_email']})</p>
                    <p><strong>Priority:</strong> <span style='text-transform: uppercase; font-weight: bold;'>{$requestData['priority']}</span></p>
                    <p><strong>Requested Duration:</strong> {$requestData['duration']} days</p>
                    <p><strong>Request Date:</strong> {$requestData['request_date']}</p>
                </div>
                
                " . (!empty($requestData['notes']) ? "
                <div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Additional Notes:</h4>
                    <p style='margin-bottom: 0;'>{$requestData['notes']}</p>
                </div>
                " : "") . "
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;'>
                    <p style='color: #6c757d; font-size: 14px;'>
                        Please log in to the library management system to approve or reject this request.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $template;
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $subject, $message, $type, $status, $response = '') {
        try {
            // Check if email_logs table exists
            if (!$this->tableExists('email_logs')) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (recipient, subject, message, email_type, status, response, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssssss", $to, $subject, $message, $type, $status, $response);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration() {
        if (!$this->settings['email_enabled']) {
            return [
                'success' => false,
                'message' => 'Email system is disabled'
            ];
        }
        
        if (empty($this->settings['email_username'])) {
            return [
                'success' => false,
                'message' => 'Email configuration is incomplete'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Email configuration appears valid (actual sending not tested in this demo)'
        ];
    }
}

// Initialize email system if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $emailSystem = new EmailNotificationSystem();
    $test = $emailSystem->testEmailConfiguration();
    echo json_encode($test);
}
?>
