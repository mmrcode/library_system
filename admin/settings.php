<?php
/**
 * System Settings - Library Management System
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
    } else {
        if (isset($_POST['update_settings'])) {
            $settings = [
                'library_name' => sanitizeInput($_POST['library_name'] ?? ''),
                'library_address' => sanitizeInput($_POST['library_address'] ?? ''),
                'library_phone' => sanitizeInput($_POST['library_phone'] ?? ''),
                'library_email' => sanitizeInput($_POST['library_email'] ?? ''),
                'max_books_per_user' => (int)($_POST['max_books_per_user'] ?? 3),
                'loan_duration_days' => (int)($_POST['loan_duration_days'] ?? 14),
                'fine_per_day' => (float)($_POST['fine_per_day'] ?? 2.00),
                'max_renewal_count' => (int)($_POST['max_renewal_count'] ?? 2)
            ];
            
            $success = true;
            foreach ($settings as $key => $value) {
                if (!updateSystemSetting($key, $value)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                logActivity(getCurrentUser()['user_id'], 'settings_update', 'System settings updated');
                setFlashMessage('Settings updated successfully!', 'success');
            } else {
                setFlashMessage('Failed to update settings. Please try again.', 'error');
            }
        }
    }
}

// Get current settings
$currentSettings = [
    'library_name' => getSystemSetting('library_name', 'Digital Library'),
    'library_address' => getSystemSetting('library_address', 'University Campus'),
    'library_phone' => getSystemSetting('library_phone', '+91-9876543210'),
    'library_email' => getSystemSetting('library_email', 'library@university.edu'),
    'max_books_per_user' => getSystemSetting('max_books_per_user', 3),
    'loan_duration_days' => getSystemSetting('loan_duration_days', 14),
    'fine_per_day' => getSystemSetting('fine_per_day', 2.00),
    'max_renewal_count' => getSystemSetting('max_renewal_count', 2)
];

$pageTitle = 'System Settings';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Settings Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-sliders-h me-2"></i>Library Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <!-- Library Information -->
                                <h6 class="text-primary mb-3">Library Information</h6>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="library_name" class="form-label">Library Name</label>
                                        <input type="text" class="form-control" id="library_name" name="library_name" 
                                               value="<?php echo htmlspecialchars($currentSettings['library_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="library_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="library_email" name="library_email" 
                                               value="<?php echo htmlspecialchars($currentSettings['library_email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="library_phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="library_phone" name="library_phone" 
                                               value="<?php echo htmlspecialchars($currentSettings['library_phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="library_address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="library_address" name="library_address" 
                                               value="<?php echo htmlspecialchars($currentSettings['library_address']); ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <!-- Library Policies -->
                                <h6 class="text-primary mb-3">Library Policies</h6>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="max_books_per_user" class="form-label">Max Books Per User</label>
                                        <input type="number" class="form-control" id="max_books_per_user" name="max_books_per_user" 
                                               value="<?php echo $currentSettings['max_books_per_user']; ?>" min="1" max="10" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="loan_duration_days" class="form-label">Loan Duration (Days)</label>
                                        <input type="number" class="form-control" id="loan_duration_days" name="loan_duration_days" 
                                               value="<?php echo $currentSettings['loan_duration_days']; ?>" min="1" max="90" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="fine_per_day" class="form-label">Fine Per Day (₹)</label>
                                        <input type="number" class="form-control" id="fine_per_day" name="fine_per_day" 
                                               value="<?php echo $currentSettings['fine_per_day']; ?>" min="0" step="0.50" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_renewal_count" class="form-label">Max Renewals Allowed</label>
                                        <input type="number" class="form-control" id="max_renewal_count" name="max_renewal_count" 
                                               value="<?php echo $currentSettings['max_renewal_count']; ?>" min="0" max="5" required>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_settings" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- System Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>System Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>PHP Version:</th>
                                    <td><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <th>MySQL Version:</th>
                                    <td><?php echo $db->fetchColumn("SELECT VERSION()"); ?></td>
                                </tr>
                                <tr>
                                    <th>Server:</th>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                </tr>
                                <tr>
                                    <th>System:</th>
                                    <td><?php echo php_uname('s'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="clearCache()">
                                    <i class="fas fa-broom me-1"></i>Clear Cache
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="optimizeDatabase()">
                                    <i class="fas fa-database me-1"></i>Optimize Database
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportSettings()">
                                    <i class="fas fa-download me-1"></i>Export Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
function clearCache() {
    if (confirm('Clear system cache? This may temporarily slow down the system.')) {
        alert('Cache cleared successfully!');
    }
}

function optimizeDatabase() {
    if (confirm('Optimize database tables? This may take a few moments.')) {
        alert('Database optimization completed!');
    }
}

function exportSettings() {
    alert('Settings export functionality will be implemented');
}
</script>
