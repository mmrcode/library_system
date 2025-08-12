<?php
/**
 * Common Utility Functions
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    // basic trimming + strip tags – good enough for forms here
    // TODO: could add more rules per field later if needed
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    // using PHP filter (kept it simple instead of a long regex)
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 */
function isValidPhone($phone) {
    $pattern = '/^[6-9]\d{9}$/';
    // Simple validation instead of regex for now -> actually using a tiny regex :)
    return preg_match($pattern, $phone);
}

/**
 * Validate date format (Y-m-d)
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    // quick check to ensure exact format match (no fancy timezone handling here)
    return $d && $d->format($format) === $date;
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    // college project constraint: not using openssl here
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calculate days between two dates
 */
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    // absolute difference in days (works fine for our use-cases)
    return $interval->days;
}

/**
 * Check if date is overdue
 */
function isOverdue($dueDate) {
    // comparing timestamps (midnight). Good enough as we don't track time of day.
    return strtotime($dueDate) < strtotime(date('Y-m-d'));
}

/**
 * Calculate fine amount
 */
function calculateFine($dueDate, $returnDate = null, $finePerDay = FINE_PER_DAY) {
    $returnDate = $returnDate ?: date('Y-m-d');
    
    if (strtotime($returnDate) <= strtotime($dueDate)) {
        return 0;
    }
    
    $daysOverdue = daysBetween($dueDate, $returnDate);
    // no grace period here because the fine module handles that separately
    return $daysOverdue * $finePerDay;   // can be optimized later if needed
}

/**
 * Get system setting value
 */
function getSystemSetting($settingName, $defaultValue = null) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT setting_value FROM system_settings WHERE setting_name = ?";
        $result = $db->fetchOne($sql, [$settingName]);
        return $result ? $result['setting_value'] : $defaultValue;
    } catch (Exception $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $defaultValue;
    }
}

/**
 * Update system setting
 */
function updateSystemSetting($settingName, $settingValue) {
    try {
        $db = Database::getInstance();
        
        // Check if setting exists
        $sql = "SELECT setting_id FROM system_settings WHERE setting_name = ?";
        $existing = $db->fetchOne($sql, [$settingName]);
        
        if ($existing) {
            return $db->update('system_settings', 
                ['setting_value' => $settingValue], 
                'setting_name = ?', 
                [$settingName]
            );
        } else {
            return $db->insert('system_settings', [
                'setting_name' => $settingName,
                'setting_value' => $settingValue
            ]);
        }
    } catch (Exception $e) {
        error_log("Error updating system setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Upload file
 */
function uploadFile($file, $uploadDir = UPLOAD_PATH, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    try {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception('File type not allowed');
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'filename' => $fileName,
                'filepath' => $filePath
            ];
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Delete file
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalRecords, $recordsPerPage = RECORDS_PER_PAGE, $baseUrl = '') {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $prevPage . '">Previous</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $pagination .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $nextPage . '">Next</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * Show alert message
 */
function showAlert($message, $type = 'info') {
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alertClass[$type] ?? 'alert-info';
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return showAlert($message, $type);
    }
    return '';
}

/**
 * Generate unique ISBN
 */
function generateISBN() {
    return '978-' . rand(1000000000, 9999999999);
}

/**
 * Validate ISBN format
 */
function isValidISBN($isbn) {
    // Remove hyphens and spaces
    $isbn = preg_replace('/[\s-]/', '', $isbn);
    
    // Check if it's 10 or 13 digits
    if (strlen($isbn) === 10) {
        return preg_match('/^\d{9}[\dX]$/', $isbn);
    } elseif (strlen($isbn) === 13) {
        return preg_match('/^\d{13}$/', $isbn);
    }
    
    return false;
}

/**
 * Get book status badge
 */
function getBookStatusBadge($availableCopies, $totalCopies) {
    if ($availableCopies == 0) {
        return '<span class="badge bg-danger">Not Available</span>';
    } elseif ($availableCopies <= 2) {
        return '<span class="badge bg-warning">Limited</span>';
    } else {
        return '<span class="badge bg-success">Available</span>';
    }
}

/**
 * Get user status badge
 */
function getUserStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'suspended' => '<span class="badge bg-danger">Suspended</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Get issue status badge
 */
function getIssueStatusBadge($status) {
    $badges = [
        'issued' => '<span class="badge bg-primary">Issued</span>',
        'returned' => '<span class="badge bg-success">Returned</span>',
        'overdue' => '<span class="badge bg-danger">Overdue</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Get fine status badge
 */
function getFineStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'waived' => '<span class="badge bg-info">Waived</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Log system activity
 */
function logSystemActivity($action, $details = '') {
    try {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => 'system',
            'record_id' => null,
            'new_values' => json_encode(['details' => $details]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $db->insert('activity_logs', $data);
    } catch (Exception $e) {
        error_log("System activity logging error: " . $e->getMessage());
    }
}

/**
 * Check if user can issue more books
 */
function canIssueMoreBooks($userId) {
    try {
        $db = Database::getInstance();
        $maxBooks = getSystemSetting('max_books_per_user', MAX_BOOKS_PER_USER);
        
        $sql = "SELECT COUNT(*) as issued_count FROM book_issues 
                WHERE user_id = ? AND status = 'issued'";
        $result = $db->fetchOne($sql, [$userId]);
        
        return ($result['issued_count'] < $maxBooks);
    } catch (Exception $e) {
        error_log("Error checking book issue limit: " . $e->getMessage());
        return false;
    }
}

/**
 * Get overdue books count for user
 */
function getOverdueBooksCount($userId) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as overdue_count FROM book_issues 
                WHERE user_id = ? AND status = 'overdue'";
        $result = $db->fetchOne($sql, [$userId]);
        
        return $result['overdue_count'];
    } catch (Exception $e) {
        error_log("Error getting overdue books count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Update overdue books status
 */
function updateOverdueBooks() {
    try {
        $db = Database::getInstance();
        $sql = "UPDATE book_issues SET status = 'overdue' 
                WHERE status = 'issued' AND due_date < CURDATE()";
        return $db->query($sql);
    } catch (Exception $e) {
        error_log("Error updating overdue books: " . $e->getMessage());
        return false;
    }
}
?>
