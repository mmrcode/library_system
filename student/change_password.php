<?php
/**
 * Student Change Password - Library Management System
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match';
        }
        
        if ($currentPassword === $newPassword) {
            $errors[] = 'New password must be different from current password';
        }
        
        // Verify current password
        if (empty($errors)) {
            $user = $db->fetchRow("SELECT password FROM users WHERE user_id = ?", [$userId]);
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            }
        }
        
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $result = $db->update('users', [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['user_id' => $userId]);
            
            if ($result) {
                logActivity($userId, 'password_change', 'Password changed successfully');
                setFlashMessage('Password changed successfully!', 'success');
                
                // Redirect to profile page
                header('Location: profile.php');
                exit;
            } else {
                setFlashMessage('Failed to change password. Please try again.', 'error');
            }
        } else {
            setFlashMessage(implode('<br>', $errors), 'error');
        }
    }
}

$pageTitle = 'Change Password';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-key me-2"></i>Change Password
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-lock me-2"></i>Change Your Password
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Password Requirements:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Minimum 6 characters long</li>
                                    <li>Must be different from your current password</li>
                                    <li>Use a combination of letters, numbers, and special characters for better security</li>
                                </ul>
                            </div>

                            <form method="POST" action="" id="changePasswordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye" id="current_password_icon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye" id="new_password_icon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <div id="password_strength" class="mt-2"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                                        </button>
                                    </div>
                                    <div id="password_match" class="form-text"></div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="change_password" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save me-1"></i>Change Password
                                    </button>
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Tips -->
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-shield-alt me-2"></i>Security Tips
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Use a unique password that you don't use elsewhere
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Include uppercase and lowercase letters, numbers, and symbols
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Avoid using personal information like birthdate or name
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Change your password regularly for better security
                                </li>
                                <li class="mb-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Never share your password with anyone
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 6) strength++;
    else feedback.push('At least 6 characters');
    
    if (password.match(/[a-z]/)) strength++;
    else feedback.push('Lowercase letter');
    
    if (password.match(/[A-Z]/)) strength++;
    else feedback.push('Uppercase letter');
    
    if (password.match(/[0-9]/)) strength++;
    else feedback.push('Number');
    
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    else feedback.push('Special character');
    
    return { strength, feedback };
}

// Update password strength indicator
function updatePasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthDiv = document.getElementById('password_strength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    const result = checkPasswordStrength(password);
    let strengthText = '';
    let strengthClass = '';
    
    switch (result.strength) {
        case 0:
        case 1:
            strengthText = 'Very Weak';
            strengthClass = 'text-danger';
            break;
        case 2:
            strengthText = 'Weak';
            strengthClass = 'text-warning';
            break;
        case 3:
            strengthText = 'Fair';
            strengthClass = 'text-info';
            break;
        case 4:
            strengthText = 'Good';
            strengthClass = 'text-primary';
            break;
        case 5:
            strengthText = 'Strong';
            strengthClass = 'text-success';
            break;
    }
    
    strengthDiv.innerHTML = `
        <small class="${strengthClass}">
            <strong>Strength: ${strengthText}</strong>
            ${result.feedback.length > 0 ? '<br>Missing: ' + result.feedback.join(', ') : ''}
        </small>
    `;
}

// Check password match
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password_match');
    
    if (confirmPassword.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Passwords match</small>';
    } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</small>';
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    
    // Real-time password strength checking
    newPasswordField.addEventListener('input', updatePasswordStrength);
    
    // Real-time password match checking
    confirmPasswordField.addEventListener('input', checkPasswordMatch);
    newPasswordField.addEventListener('input', checkPasswordMatch);
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = newPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        
        if (!currentPassword) {
            e.preventDefault();
            alert('Please enter your current password');
            document.getElementById('current_password').focus();
            return;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('New password must be at least 6 characters long');
            newPasswordField.focus();
            return;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirmation do not match');
            confirmPasswordField.focus();
            return;
        }
        
        if (currentPassword === newPassword) {
            e.preventDefault();
            alert('New password must be different from current password');
            newPasswordField.focus();
            return;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Changing Password...';
    });
});
</script>
