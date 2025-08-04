<?php
/**
 * Database Configuration File
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

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'library_management');

// Application configuration
define('APP_NAME', 'Library Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/library_system/');

// Security configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 6);

// File upload configuration
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination configuration
define('RECORDS_PER_PAGE', 10);

// Library settings (can be overridden by database settings)
define('DEFAULT_ISSUE_DAYS', 14);
define('MAX_BOOKS_PER_USER', 5);
define('FINE_PER_DAY', 1.00);
define('MAX_RENEWAL_TIMES', 2);

// Date and time configuration
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone setting
date_default_timezone_set('Asia/Kolkata');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
