<?php
/**
 * Student Profile - Library Management System
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
require_once '../includes/student_functions.php';

// Require student access
requireStudent();

$db = Database::getInstance();
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
    } else {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $department = sanitizeInput($_POST['department'] ?? '');
        
        $errors = [];
        
        // Validation
        if (empty($fullName)) {
            $errors[] = 'Full name is required';
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = 'Phone number must be 10 digits';
        }
        
        // Check if email is already used by another user
        if (!empty($email)) {
            $existingUser = $db->fetchRow("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $userId]);
            if ($existingUser) {
                $errors[] = 'Email is already in use by another user';
            }
        }
        
        if (empty($errors)) {
            $updateData = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'department' => $department,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->update('users', $updateData, ['user_id' => $userId]);
            
            if ($result) {
                // Update session data
                $_SESSION['user']['full_name'] = $fullName;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;
                $_SESSION['user']['address'] = $address;
                $_SESSION['user']['department'] = $department;
                
                logActivity($userId, 'profile_update', 'Profile updated successfully');
                setFlashMessage('Profile updated successfully!', 'success');
                
                // Refresh current user data
                $currentUser = getCurrentUser();
            } else {
                setFlashMessage('Failed to update profile. Please try again.', 'error');
            }
        } else {
            setFlashMessage(implode('<br>', $errors), 'error');
        }
    }
}

// Get user statistics
$userStats = $db->fetchRow("
    SELECT 
        COUNT(CASE WHEN bi.status IN ('issued', 'overdue') THEN 1 END) as active_issues,
        COUNT(CASE WHEN bi.status = 'returned' THEN 1 END) as total_returned,
        COUNT(*) as total_issues,
        COALESCE(SUM(CASE WHEN f.status = 'pending' THEN f.fine_amount ELSE 0 END), 0) as pending_fines,
        COALESCE(SUM(CASE WHEN f.status = 'paid' THEN f.fine_amount ELSE 0 END), 0) as total_fines_paid
    FROM book_issues bi
    LEFT JOIN fines f ON bi.issue_id = f.issue_id
    WHERE bi.user_id = ?
", [$userId]);

$pageTitle = 'My Profile';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-user me-2"></i>My Profile
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="change_password.php" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-key me-1"></i>Change Password
                        </a>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user-edit me-2"></i>Profile Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_number" class="form-label">Registration Number</label>
                                        <input type="text" class="form-control" id="registration_number" 
                                               value="<?php echo !empty($currentUser['registration_number']) ? htmlspecialchars($currentUser['registration_number']) : ''; ?>" 
                                               readonly disabled>
                                        <div class="form-text">Registration number cannot be changed</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                               pattern="[0-9]{10}" maxlength="10">
                                        <div class="form-text">Enter 10-digit phone number</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" 
                                               value="<?php echo htmlspecialchars($currentUser['department'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="user_type" class="form-label">User Type</label>
                                        <input type="text" class="form-control" id="user_type" 
                                               value="<?php echo ucfirst($currentUser['user_type']); ?>" 
                                               readonly disabled>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="created_at" class="form-label">Member Since</label>
                                        <input type="text" class="form-control" id="created_at" 
                                               value="<?php echo formatDate($currentUser['created_at']); ?>" 
                                               readonly disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Account Status</label>
                                        <input type="text" class="form-control" id="status" 
                                               value="<?php echo ucfirst($currentUser['status']); ?>" 
                                               readonly disabled>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-chart-bar me-2"></i>Account Statistics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <h4 class="text-primary"><?php echo $userStats['active_issues'] ?? 0; ?></h4>
                                    <small>Active Issues</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-success"><?php echo $userStats['total_returned'] ?? 0; ?></h4>
                                    <small>Books Returned</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-info"><?php echo $userStats['total_issues'] ?? 0; ?></h4>
                                    <small>Total Issues</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-warning">₹<?php echo number_format($userStats['pending_fines'] ?? 0, 2); ?></h4>
                                    <small>Pending Fines</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="my_books.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-book me-2"></i>View My Books
                                </a>
                                <a href="history.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-history me-2"></i>Reading History
                                </a>
                                <a href="search_books.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-search me-2"></i>Search Books
                                </a>
                                <a href="change_password.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-secondary">
                                <i class="fas fa-info-circle me-2"></i>Account Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Library Policies:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Maximum <?php echo getSystemSetting('max_books_per_user', MAX_BOOKS_PER_USER); ?> books can be issued at a time</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Default issue period is <?php echo getSystemSetting('default_issue_days', DEFAULT_ISSUE_DAYS); ?> days</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Fine rate: ₹<?php echo getSystemSetting('fine_per_day', FINE_PER_DAY); ?> per day for overdue books</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Books can be renewed if no pending fines</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Contact Information:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-envelope text-primary me-2"></i><?php echo getSystemSetting('library_email', 'library@university.edu'); ?></li>
                                        <li><i class="fas fa-phone text-primary me-2"></i><?php echo getSystemSetting('library_phone', '+91-XXXXXXXXXX'); ?></li>
                                        <li><i class="fas fa-clock text-primary me-2"></i><?php echo getSystemSetting('working_hours', '9:00 AM - 6:00 PM'); ?></li>
                                        <li><i class="fas fa-map-marker-alt text-primary me-2"></i><?php echo getSystemSetting('library_address', 'University Campus'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const phoneInput = document.getElementById('phone');
    
    // Phone number validation
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        
        if (!fullName) {
            e.preventDefault();
            alert('Full name is required');
            document.getElementById('full_name').focus();
            return;
        }
        
        if (email && !isValidEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            document.getElementById('email').focus();
            return;
        }
        
        if (phone && (phone.length !== 10 || !/^[0-9]{10}$/.test(phone))) {
            e.preventDefault();
            alert('Please enter a valid 10-digit phone number');
            document.getElementById('phone').focus();
            return;
        }
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>
