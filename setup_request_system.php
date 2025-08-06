<?php
/**
 * Setup Book Request System - Library Management System
 * 
 * This script creates the necessary database tables for the book request system,
 * messaging system, notifications, email logs, and system settings.
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

define('LIBRARY_SYSTEM', true);

// Include required files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Require admin access
requireAdmin();

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Read SQL file
$sqlFile = 'database/book_request_system.sql';
if (!file_exists($sqlFile)) {
    die("SQL setup file not found: {$sqlFile}");
}

$sqlContent = file_get_contents($sqlFile);
if (!$sqlContent) {
    die("Failed to read SQL setup file: {$sqlFile}");
}

// Split SQL content into individual statements
$statements = explode(';', $sqlContent);
$successCount = 0;
$errorCount = 0;
$errors = [];

// Execute each statement
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) {
        continue;
    }
    
    try {
        if ($conn->query($statement)) {
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = $conn->error;
        }
    } catch (Exception $e) {
        $errorCount++;
        $errors[] = $e->getMessage();
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Book Request System - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Book Request System Setup</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($errorCount === 0): ?>
                            <div class="alert alert-success">
                                <h4>✅ Setup Completed Successfully!</h4>
                                <p>All database tables have been created successfully.</p>
                                <p>Created <?php echo $successCount; ?> tables:</p>
                                <ul>
                                    <li>book_requests</li>
                                    <li>messages</li>
                                    <li>notifications</li>
                                    <li>email_logs</li>
                                    <li>system_settings</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h4>⚠️ Setup Completed with Issues</h4>
                                <p><?php echo $successCount; ?> tables created successfully.</p>
                                <p><?php echo $errorCount; ?> errors occurred during setup:</p>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h5>📚 Book Request and Communication System Features:</h5>
                            <ul>
                                <li>📧 Email Notification System - Automatic email alerts to librarians</li>
                                <li>📝 Book Request Queue System - Students can request books online</li>
                                <li>🔔 In-app Messaging System - Direct communication between students and librarians</li>
                                <li>📋 Request Tracking Dashboard - Comprehensive management interface</li>
                            </ul>
                        </div>
                        
                        <a href="index.php" class="btn btn-primary">Return to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
