<?php
/**
 * Backup & Restore - Library Management System
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
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        $backupDir = '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'library_backup_' . $timestamp . '.sql';
        
        // Get database connection
        $connection = $db->getConnection();
        
        // Get all tables
        $tables = [];
        $result = $connection->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $backupContent = "-- Library Management System Database Backup\n";
        $backupContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Database: " . DB_NAME . "\n\n";
        
        // Add database creation
        $backupContent .= "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`;\n";
        $backupContent .= "USE `" . DB_NAME . "`;\n\n";
        
        // Backup each table
        foreach ($tables as $table) {
            // Get table structure
            $result = $connection->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_row();
            $backupContent .= $row[1] . ";\n\n";
            
            // Get table data
            $result = $connection->query("SELECT * FROM `$table`");
            if ($result->num_rows > 0) {
                $backupContent .= "-- Data for table `$table`\n";
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($value) use ($connection) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . $connection->real_escape_string($value) . "'";
                    }, $row);
                    
                    $backupContent .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backupContent .= "\n";
            }
        }
        
        // Write backup file
        if (file_put_contents($backupFile, $backupContent)) {
            $message = "Backup created successfully: " . basename($backupFile);
            $messageType = 'success';
        } else {
            throw new Exception("Failed to write backup file");
        }
        
    } catch (Exception $e) {
        $message = "Backup failed: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle backup restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid backup file");
        }
        
        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $fileName = $_FILES['backup_file']['name'];
        
        // Validate file extension
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'sql') {
            throw new Exception("Please select a valid SQL backup file");
        }
        
        // Read backup content
        $backupContent = file_get_contents($uploadedFile);
        if ($backupContent === false) {
            throw new Exception("Failed to read backup file");
        }
        
        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $backupContent)));
        
        $connection = $db->getConnection();
        
        // Execute each query
        foreach ($queries as $query) {
            if (!empty($query)) {
                if (!$connection->query($query)) {
                    throw new Exception("Query failed: " . $connection->error);
                }
            }
        }
        
        $message = "Backup restored successfully from: " . $fileName;
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = "Restore failed: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get existing backups
$backupDir = '../backups/';
$existingBackups = [];
if (is_dir($backupDir)) {
    $files = glob($backupDir . '*.sql');
    foreach ($files as $file) {
        $existingBackups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file),
            'path' => $file
        ];
    }
    // Sort by date (newest first)
    usort($existingBackups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

$pageTitle = 'Backup & Restore';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-database me-2"></i>Backup & Restore
                </h1>
            </div>

            <!-- Flash Message -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo ($messageType === 'success') ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Create Backup -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-download me-2"></i>Create Backup
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Create a complete backup of your library database. This will include all tables, data, and structure.
                            </p>
                            <form method="POST" action="">
                                <button type="submit" name="create_backup" class="btn btn-primary" 
                                        onclick="return confirm('Are you sure you want to create a backup?')">
                                    <i class="fas fa-download me-2"></i>Create Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Restore Backup -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-upload me-2"></i>Restore Backup
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <strong>Warning:</strong> Restoring a backup will overwrite all current data. Make sure to backup current data first.
                            </p>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="backup_file" class="form-label">Select Backup File</label>
                                    <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                           accept=".sql" required>
                                    <div class="form-text">Select a .sql backup file to restore</div>
                                </div>
                                <button type="submit" name="restore_backup" class="btn btn-warning" 
                                        onclick="return confirm('WARNING: This will overwrite all current data. Are you sure you want to proceed?')">
                                    <i class="fas fa-upload me-2"></i>Restore Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Backups -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Existing Backups
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($existingBackups)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No backups found</h5>
                            <p class="text-muted">Create your first backup to see it listed here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Backup File</th>
                                        <th>Size</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existingBackups as $backup): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-archive text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                            </td>
                                            <td><?php echo formatBytes($backup['size']); ?></td>
                                            <td><?php echo date('M j, Y g:i A', $backup['date']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../backups/<?php echo urlencode($backup['name']); ?>" 
                                                       class="btn btn-outline-primary" download>
                                                        <i class="fas fa-download me-1"></i>Download
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Database Information -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Database Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Database Name:</strong></td>
                                    <td><?php echo htmlspecialchars(DB_NAME); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Host:</strong></td>
                                    <td><?php echo htmlspecialchars(DB_HOST); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tables:</strong></td>
                                    <td>
                                        <?php 
                                        $connection = $db->getConnection();
                                        $result = $connection->query("SHOW TABLES");
                                        echo $result->num_rows;
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total Records:</strong></td>
                                    <td>
                                        <?php 
                                        $totalRecords = 0;
                                        $result = $connection->query("SHOW TABLES");
                                        while ($row = $result->fetch_row()) {
                                            $tableName = $row[0];
                                            $countResult = $connection->query("SELECT COUNT(*) as count FROM `$tableName`");
                                            $countRow = $countResult->fetch_assoc();
                                            $totalRecords += $countRow['count'];
                                        }
                                        echo number_format($totalRecords);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Backup Directory:</strong></td>
                                    <td><code>backups/</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Backup:</strong></td>
                                    <td>
                                        <?php 
                                        if (!empty($existingBackups)) {
                                            echo date('M j, Y g:i A', $existingBackups[0]['date']);
                                        } else {
                                            echo '<span class="text-muted">Never</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function deleteBackup(fileName) {
    if (confirm('Are you sure you want to delete this backup file?')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_backup';
        input.value = fileName;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

include '../includes/admin_footer.php';
?> 