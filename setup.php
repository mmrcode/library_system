<?php
/**
 * Library Management System Setup Script
 * This script helps with initial setup and database creation
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

// Define constant to allow included files to run
define('LIBRARY_SYSTEM', true);

// Include configuration
require_once 'includes/config.php';

// Check if setup is already completed
$setupFile = 'setup_completed.txt';
if (file_exists($setupFile)) {
    header('Location: index.php');
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_database'])) {
        try {
            // Create database connection without database name
            $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
            
            if ($connection->connect_error) {
                throw new Exception("Connection failed: " . $connection->connect_error);
            }
            
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
            if ($connection->query($sql) === TRUE) {
                $success = "Database created successfully!";
                $step = 2;
            } else {
                throw new Exception("Error creating database: " . $connection->error);
            }
            
            $connection->close();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['import_schema'])) {
        try {
            require_once 'includes/database.php';
            $db = Database::getInstance();
            
            // Read and execute schema file
            $schemaFile = 'database/library_db.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $queries = explode(';', $sql);
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->query($query);
                    }
                }
                $success = "Database schema imported successfully!";
                $step = 3;
            } else {
                throw new Exception("Schema file not found!");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['import_sample_data'])) {
        try {
            require_once 'includes/database.php';
            $db = Database::getInstance();
            
            // Read and execute sample data file
            $dataFile = 'database/sample_data.sql';
            if (file_exists($dataFile)) {
                $sql = file_get_contents($dataFile);
                $queries = explode(';', $sql);
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->query($query);
                    }
                }
                $success = "Sample data imported successfully!";
                $step = 4;
            } else {
                throw new Exception("Sample data file not found!");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['complete_setup'])) {
        // Create setup completion file
        file_put_contents($setupFile, date('Y-m-d H:i:s') . ' - Setup completed');
        $success = "Setup completed successfully! Redirecting to login page...";
        header("Refresh: 2; URL=index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Library Management System Setup
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Progress Bar -->
                        <div class="progress mb-4">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo ($step / 4) * 100; ?>%" 
                                 aria-valuenow="<?php echo $step; ?>" 
                                 aria-valuemin="0" aria-valuemax="4">
                                Step <?php echo $step; ?> of 4
                            </div>
                        </div>
                        
                        <!-- Step 1: Database Creation -->
                        <?php if ($step == 1): ?>
                            <h5>Step 1: Database Setup</h5>
                            <p class="text-muted">First, we need to create the database for the library management system.</p>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Requirements:</h6>
                                <ul class="mb-0">
                                    <li>XAMPP must be running (Apache & MySQL)</li>
                                    <li>MySQL server should be accessible</li>
                                    <li>Database credentials should be configured in <code>includes/config.php</code></li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="create_database" class="btn btn-primary">
                                    <i class="fas fa-database me-2"></i>Create Database
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Step 2: Schema Import -->
                        <?php if ($step == 2): ?>
                            <h5>Step 2: Import Database Schema</h5>
                            <p class="text-muted">Now we'll import the database structure and tables.</p>
                            
                            <form method="POST">
                                <button type="submit" name="import_schema" class="btn btn-primary">
                                    <i class="fas fa-table me-2"></i>Import Schema
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Step 3: Sample Data -->
                        <?php if ($step == 3): ?>
                            <h5>Step 3: Import Sample Data</h5>
                            <p class="text-muted">Import sample data including admin user, categories, and books.</p>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Note:</h6>
                                <p class="mb-0">This will create sample data including an admin user with credentials:</p>
                                <ul class="mb-0">
                                    <li><strong>Username:</strong> admin</li>
                                    <li><strong>Password:</strong> password123</li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="import_sample_data" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Import Sample Data
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Step 4: Complete Setup -->
                        <?php if ($step == 4): ?>
                            <h5>Step 4: Complete Setup</h5>
                            <p class="text-muted">Setup is almost complete! Click the button below to finish.</p>
                            
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Setup Summary:</h6>
                                <ul class="mb-0">
                                    <li>✅ Database created successfully</li>
                                    <li>✅ Database schema imported</li>
                                    <li>✅ Sample data imported</li>
                                    <li>✅ System ready for use</li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="complete_setup" class="btn btn-success">
                                    <i class="fas fa-rocket me-2"></i>Complete Setup
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Library Management System v1.0.0<br>
                                Developed by Mohammad Muqsit Raja (BCA22739)<br>
                                University of Mysore - 2025
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 