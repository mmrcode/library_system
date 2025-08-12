<?php
/**
 * Fine Calculation Functions
 * Library Management System - Fine Management Module
 * 
 * This module handles all fine calculation, tracking, and reporting functionality
 * for overdue books and library materials.
 */

require_once 'config.php';
require_once 'email_functions.php';

class FineCalculator {
    private $db;
    private $fine_rates;
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadFineRates();
    }
    
    /**
     * Load fine rates from system settings
     * NOTE: using defaults and then overriding from DB if available.
     * Keeping it simple for project submission, can move to config later.
     */
    private function loadFineRates() {
        $this->fine_rates = [
            'regular_book' => 1.00,      // ₹1.00 per day
            'reference_book' => 2.00,    // ₹2.00 per day
            'journal' => 1.50,           // ₹1.50 per day
            'magazine' => 0.50,          // ₹0.50 per day
            'maximum_fine' => 50.00,     // Maximum fine per book
            'grace_period' => 2          // Grace period in days
        ];
        
        // try to load custom rates from DB (if admin changed settings)
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'fine_%'");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = str_replace('fine_', '', $row['setting_key']);
                $this->fine_rates[$key] = floatval($row['setting_value']);
            }
        } catch (Exception $e) {
            // if settings table isn't ready yet during setup, just use defaults
            error_log("Error loading fine rates: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate fine for a specific book issue
     * Casual note: we consider grace period here and cap at maximum fine.
     * Also the daily rate depends on book type (basic mapping only).
     */
    public function calculateFine($issue_id, $return_date = null) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    bi.issue_id,
                    bi.book_id,
                    bi.user_id,
                    bi.issue_date,
                    bi.due_date,
                    bi.return_date,
                    b.book_type,
                    b.title as book_title,
                    u.name as user_name,
                    u.email as user_email
                FROM book_issues bi
                JOIN books b ON bi.book_id = b.book_id
                JOIN users u ON bi.user_id = u.user_id
                WHERE bi.issue_id = ?
            ");
            
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$issue) {
                throw new Exception("Issue record not found");
            }
            
            // Use provided return date or current date (so it works for previews too)
            $actual_return_date = $return_date ? new DateTime($return_date) : new DateTime();
            $due_date = new DateTime($issue['due_date']);
            
            // Calculate days overdue
            $days_overdue = 0;
            if ($actual_return_date > $due_date) {
                $interval = $due_date->diff($actual_return_date);
                $days_overdue = $interval->days;
                
                // Apply grace period
                $days_overdue = max(0, $days_overdue - $this->fine_rates['grace_period']);
            }
            
            // Calculate fine amount
            $fine_amount = 0;
            if ($days_overdue > 0) {
                $book_type = strtolower($issue['book_type']);   // normalize type
                $daily_rate = $this->fine_rates['regular_book']; // default rate if unknown
                
                // Apply specific rates based on book type (simple mapping, not exhaustive)
                if (isset($this->fine_rates[$book_type . '_book'])) {
                    $daily_rate = $this->fine_rates[$book_type . '_book'];
                } elseif (isset($this->fine_rates[$book_type])) {
                    $daily_rate = $this->fine_rates[$book_type];
                }
                
                $fine_amount = $days_overdue * $daily_rate;
                
                // Apply maximum fine limit
                $fine_amount = min($fine_amount, $this->fine_rates['maximum_fine']);
            }
            
            return [
                'issue_id' => $issue_id,
                'book_id' => $issue['book_id'],
                'user_id' => $issue['user_id'],
                'book_title' => $issue['book_title'],
                'user_name' => $issue['user_name'],
                'user_email' => $issue['user_email'],
                'issue_date' => $issue['issue_date'],
                'due_date' => $issue['due_date'],
                'return_date' => $return_date,
                'days_overdue' => $days_overdue,
                'daily_rate' => $daily_rate,
                'fine_amount' => round($fine_amount, 2),
                'status' => $days_overdue > 0 ? 'overdue' : 'on_time'
            ];
            
        } catch (Exception $e) {
            error_log("Error calculating fine: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Record fine in database
     */
    public function recordFine($fine_data) {
        try {
            // Check if fine already exists
            $stmt = $this->db->prepare("SELECT fine_id FROM fines WHERE issue_id = ?");
            $stmt->execute([$fine_data['issue_id']]);
            
            if ($stmt->fetch()) {
                // Update existing fine
                $stmt = $this->db->prepare("
                    UPDATE fines SET 
                        fine_amount = ?,
                        days_overdue = ?,
                        calculated_date = NOW(),
                        status = ?
                    WHERE issue_id = ?
                ");
                $stmt->execute([
                    $fine_data['fine_amount'],
                    $fine_data['days_overdue'],
                    $fine_data['status'],
                    $fine_data['issue_id']
                ]);
            } else {
                // Insert new fine record
                $stmt = $this->db->prepare("
                    INSERT INTO fines (
                        issue_id, user_id, book_id, fine_amount, 
                        days_overdue, calculated_date, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())
                ");
                $stmt->execute([
                    $fine_data['issue_id'],
                    $fine_data['user_id'],
                    $fine_data['book_id'],
                    $fine_data['fine_amount'],
                    $fine_data['days_overdue'],
                    $fine_data['status']
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error recording fine: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all overdue books and calculate fines
     */
    public function calculateAllOverdueFines() {
        try {
            $stmt = $this->db->prepare("
                SELECT issue_id 
                FROM book_issues 
                WHERE return_date IS NULL 
                AND due_date < CURDATE()
            ");
            $stmt->execute();
            
            $calculated_fines = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fine_data = $this->calculateFine($row['issue_id']);
                if ($fine_data['fine_amount'] > 0) {
                    $this->recordFine($fine_data);
                    $calculated_fines[] = $fine_data;
                }
            }
            
            return $calculated_fines;
        } catch (Exception $e) {
            error_log("Error calculating all overdue fines: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get fine report data
     */
    public function getFineReport($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Build WHERE clause based on filters
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "f.calculated_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "f.calculated_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where_conditions[] = "f.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['user_type']) && $filters['user_type'] !== 'all') {
                $where_conditions[] = "u.user_type = ?";
                $params[] = $filters['user_type'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT 
                    f.fine_id,
                    f.issue_id,
                    f.user_id,
                    f.book_id,
                    f.fine_amount,
                    f.days_overdue,
                    f.status,
                    f.calculated_date,
                    f.paid_date,
                    f.payment_method,
                    u.name as user_name,
                    u.email as user_email,
                    u.user_type,
                    b.title as book_title,
                    b.book_type,
                    bi.issue_date,
                    bi.due_date,
                    bi.return_date
                FROM fines f
                JOIN users u ON f.user_id = u.user_id
                JOIN books b ON f.book_id = b.book_id
                JOIN book_issues bi ON f.issue_id = bi.issue_id
                $where_clause
                ORDER BY f.calculated_date DESC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error generating fine report: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get fine statistics
     */
    public function getFineStatistics() {
        try {
            $stats = [];
            
            // Total outstanding fines
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_fines,
                    SUM(fine_amount) as total_amount
                FROM fines 
                WHERE status IN ('pending', 'overdue')
            ");
            $stmt->execute();
            $outstanding = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Collected fines this month
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as collected_count,
                    SUM(fine_amount) as collected_amount
                FROM fines 
                WHERE status = 'paid' 
                AND MONTH(paid_date) = MONTH(CURDATE())
                AND YEAR(paid_date) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $collected = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Average fine per item
            $stmt = $this->db->prepare("
                SELECT AVG(fine_amount) as avg_fine
                FROM fines 
                WHERE fine_amount > 0
            ");
            $stmt->execute();
            $average = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Students with fines
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_id) as students_with_fines
                FROM fines 
                WHERE status IN ('pending', 'overdue')
            ");
            $stmt->execute();
            $students = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_outstanding' => $outstanding['total_amount'] ?? 0,
                'total_fines_count' => $outstanding['total_fines'] ?? 0,
                'collected_this_month' => $collected['collected_amount'] ?? 0,
                'collected_count' => $collected['collected_count'] ?? 0,
                'average_fine' => $average['avg_fine'] ?? 0,
                'students_with_fines' => $students['students_with_fines'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Error getting fine statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process fine payment
     */
    public function processFinePayment($fine_id, $payment_method = 'cash', $notes = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE fines SET 
                    status = 'paid',
                    paid_date = NOW(),
                    payment_method = ?,
                    payment_notes = ?
                WHERE fine_id = ?
            ");
            
            $result = $stmt->execute([$payment_method, $notes, $fine_id]);
            
            if ($result) {
                // Log payment transaction
                $this->logFineTransaction($fine_id, 'payment', $payment_method, $notes);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error processing fine payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Waive fine
     */
    public function waiveFine($fine_id, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE fines SET 
                    status = 'waived',
                    waived_date = NOW(),
                    waived_reason = ?
                WHERE fine_id = ?
            ");
            
            $result = $stmt->execute([$reason, $fine_id]);
            
            if ($result) {
                // Log waiver transaction
                $this->logFineTransaction($fine_id, 'waiver', 'administrative', $reason);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error waiving fine: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send fine reminder
     */
    public function sendFineReminder($user_id) {
        try {
            // Get user's outstanding fines
            $stmt = $this->db->prepare("
                SELECT 
                    f.*,
                    u.name as user_name,
                    u.email as user_email,
                    b.title as book_title
                FROM fines f
                JOIN users u ON f.user_id = u.user_id
                JOIN books b ON f.book_id = b.book_id
                WHERE f.user_id = ? 
                AND f.status IN ('pending', 'overdue')
                ORDER BY f.calculated_date DESC
            ");
            
            $stmt->execute([$user_id]);
            $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($fines)) {
                return false;
            }
            
            // Calculate total outstanding amount
            $total_amount = array_sum(array_column($fines, 'fine_amount'));
            
            // Send email reminder
            $email_functions = new EmailFunctions($this->db);
            $subject = "Library Fine Reminder - Outstanding Amount: ₹" . number_format($total_amount, 2);
            
            $message = $this->generateFineReminderEmail($fines, $total_amount);
            
            $result = $email_functions->sendEmail(
                $fines[0]['user_email'],
                $fines[0]['user_name'],
                $subject,
                $message
            );
            
            if ($result) {
                // Update reminder sent date
                $stmt = $this->db->prepare("
                    UPDATE fines SET 
                        last_reminder_date = NOW(),
                        reminder_count = reminder_count + 1
                    WHERE user_id = ? AND status IN ('pending', 'overdue')
                ");
                $stmt->execute([$user_id]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error sending fine reminder: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate fine reminder email content
     */
    private function generateFineReminderEmail($fines, $total_amount) {
        $message = "<h2>Library Fine Reminder</h2>";
        $message .= "<p>Dear " . $fines[0]['user_name'] . ",</p>";
        $message .= "<p>This is a friendly reminder that you have outstanding library fines totaling <strong>₹" . number_format($total_amount, 2) . "</strong>.</p>";
        
        $message .= "<h3>Outstanding Fines:</h3>";
        $message .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $message .= "<tr><th>Book Title</th><th>Days Overdue</th><th>Fine Amount</th></tr>";
        
        foreach ($fines as $fine) {
            $message .= "<tr>";
            $message .= "<td>" . htmlspecialchars($fine['book_title']) . "</td>";
            $message .= "<td>" . $fine['days_overdue'] . " days</td>";
            $message .= "<td>₹" . number_format($fine['fine_amount'], 2) . "</td>";
            $message .= "</tr>";
        }
        
        $message .= "</table>";
        $message .= "<p>Please visit the library to settle these fines at your earliest convenience.</p>";
        $message .= "<p>Thank you for your cooperation.</p>";
        $message .= "<p>Best regards,<br>Library Management Team</p>";
        
        return $message;
    }
    
    /**
     * Log fine transaction
     */
    private function logFineTransaction($fine_id, $transaction_type, $method, $notes) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fine_transactions (
                    fine_id, transaction_type, transaction_method, 
                    notes, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$fine_id, $transaction_type, $method, $notes]);
        } catch (Exception $e) {
            error_log("Error logging fine transaction: " . $e->getMessage());
        }
    }
}

/**
 * Initialize fine calculation system
 * This is called from admin side during first load or setup.
 * Creating tables if they don't exist. No migrations here to keep it light.
 */
function initializeFineSystem($db) {
    try {
        // Create fines table if not exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS fines (
            fine_id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            days_overdue INT NOT NULL DEFAULT 0,
            status ENUM('pending', 'paid', 'waived', 'overdue') DEFAULT 'pending',
            calculated_date DATETIME NOT NULL,
            paid_date DATETIME NULL,
            waived_date DATETIME NULL,
            payment_method VARCHAR(50) NULL,
            payment_notes TEXT NULL,
            waived_reason TEXT NULL,
            last_reminder_date DATETIME NULL,
            reminder_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (book_id) REFERENCES books(book_id),
            INDEX idx_user_status (user_id, status),
            INDEX idx_calculated_date (calculated_date)
            )
        ");
        
        // Create fine transactions table
        $db->exec("
            CREATE TABLE IF NOT EXISTS fine_transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                fine_id INT NOT NULL,
                transaction_type ENUM('payment', 'waiver', 'adjustment') NOT NULL,
                transaction_method VARCHAR(50) NOT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (fine_id) REFERENCES fines(fine_id)
            )
        ");
        
        return true;
    } catch (Exception $e) {
        error_log("Error initializing fine system: " . $e->getMessage());
        return false;
    }
}

// Helper functions for quick access
function calculateBookFine($db, $issue_id, $return_date = null) {
    $calculator = new FineCalculator($db);
    return $calculator->calculateFine($issue_id, $return_date);
}

function getFineReport($db, $filters = []) {
    $calculator = new FineCalculator($db);
    return $calculator->getFineReport($filters);
}

function getFineStatistics($db) {
    $calculator = new FineCalculator($db);
    return $calculator->getFineStatistics();
}

function sendFineReminders($db, $user_ids = []) {
    $calculator = new FineCalculator($db);
    $results = [];
    
    if (empty($user_ids)) {
        // Get all users with outstanding fines
        $stmt = $db->prepare("SELECT DISTINCT user_id FROM fines WHERE status IN ('pending', 'overdue')");
        $stmt->execute();
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    foreach ($user_ids as $user_id) {
        $results[$user_id] = $calculator->sendFineReminder($user_id);
    }
    
    return $results;
}
?>
