<?php
/**
 * Add New User - Library Management System
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
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input data
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $user_type = sanitizeInput($_POST['user_type']);
        $registration_number = sanitizeInput($_POST['registration_number']);
        $department = sanitizeInput($_POST['department']);
        $year_of_study = (int)$_POST['year_of_study'];
        
        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (!empty($phone) && !isValidPhone($phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        if (!in_array($user_type, ['student', 'faculty'])) {
            $errors[] = 'Please select a valid user type.';
        }
        
        if ($user_type === 'student' && empty($registration_number)) {
            $errors[] = 'Registration number is required for students.';
        }
        
        if ($year_of_study > 0 && ($year_of_study < 1 || $year_of_study > 6)) {
            $errors[] = 'Year of study must be between 1 and 6.';
        }
        
        // Check if username already exists
        if (empty($errors)) {
            $existingUser = $db->fetchOne("SELECT user_id FROM users WHERE username = ?", [$username]);
            if ($existingUser) {
                $errors[] = 'Username already exists. Please choose a different username.';
            }
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $existingEmail = $db->fetchOne("SELECT user_id FROM users WHERE email = ?", [$email]);
            if ($existingEmail) {
                $errors[] = 'Email address already exists. Please use a different email.';
            }
        }
        
        // Check if registration number already exists (for students)
        if (empty($errors) && $user_type === 'student' && !empty($registration_number)) {
            $existingReg = $db->fetchOne("SELECT user_id FROM users WHERE registration_number = ?", [$registration_number]);
            if ($existingReg) {
                $errors[] = 'Registration number already exists. Please check and enter the correct number.';
            }
        }
        
        // If no errors, create the user
        if (empty($errors)) {
            try {
                $userData = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'address' => $address ?: null,
                    'user_type' => $user_type,
                    'registration_number' => $registration_number ?: null,
                    'department' => $department ?: null,
                    'year_of_study' => $year_of_study ?: null,
                    'status' => 'active'
                ];
                
                $userId = $db->insert('users', $userData);
                
                if ($userId) {
                    logSystemActivity('USER_ADD', "Added new $user_type: $full_name ($username)");
                    redirectWithMessage('users.php', 'User added successfully!', 'success');
                } else {
                    $errors[] = 'Failed to add user. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Error adding user: " . $e->getMessage());
                $errors[] = 'An error occurred while adding the user.';
            }
        }
    }
}

$pageTitle = 'Add New User';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="users.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Users
                    </a>
                </div>
            </div>

            <!-- Display errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>User Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="addUserForm">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                
                                <!-- User Type Selection -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label class="form-label">User Type <span class="text-danger">*</span></label>
                                        <div class="btn-group w-100" role="group" aria-label="User Type">
                                            <input type="radio" class="btn-check" name="user_type" id="student" value="student" 
                                                   <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'student') ? 'checked' : 'checked'; ?>>
                                            <label class="btn btn-outline-primary" for="student">
                                                <i class="fas fa-user-graduate me-2"></i>Student
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="user_type" id="faculty" value="faculty"
                                                   <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'faculty') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-primary" for="faculty">
                                                <i class="fas fa-chalkboard-teacher me-2"></i>Faculty
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Basic Information -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                               required>
                                        <div class="form-text">3+ characters, letters, numbers, and underscores only</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <!-- Contact Information -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                               placeholder="10-digit mobile number">
                                    </div>
                                </div>
                                
                                <!-- Academic Information -->
                                <div id="academic-info">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="registration_number" class="form-label">
                                                Registration Number <span class="text-danger student-required">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="registration_number" name="registration_number" 
                                                   value="<?php echo isset($_POST['registration_number']) ? htmlspecialchars($_POST['registration_number']) : ''; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-select" id="department" name="department">
                                                <option value="">Select Department</option>
                                                <option value="Computer Science" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                                <option value="Information Technology" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                                <option value="Electronics" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                                <option value="Mechanical" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                                                <option value="Civil" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Civil') ? 'selected' : ''; ?>>Civil</option>
                                                <option value="Mathematics" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                                <option value="Physics" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                                                <option value="Chemistry" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                                                <option value="English" <?php echo (isset($_POST['department']) && $_POST['department'] == 'English') ? 'selected' : ''; ?>>English</option>
                                                <option value="Other" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row" id="student-year" style="display: none;">
                                        <div class="col-md-6 mb-3">
                                            <label for="year_of_study" class="form-label">Year of Study</label>
                                            <select class="form-select" id="year_of_study" name="year_of_study">
                                                <option value="0">Select Year</option>
                                                <option value="1" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                                                <option value="2" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                                <option value="3" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                                <option value="4" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                                                <option value="5" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '5') ? 'selected' : ''; ?>>5th Year</option>
                                                <option value="6" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '6') ? 'selected' : ''; ?>>6th Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" 
                                              placeholder="Complete address..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="users.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Add User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Help Panel -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>User Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <h6>Required Fields</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Full Name</li>
                                <li><i class="fas fa-check text-success me-2"></i>Username</li>
                                <li><i class="fas fa-check text-success me-2"></i>Password</li>
                                <li><i class="fas fa-check text-success me-2"></i>Email Address</li>
                                <li><i class="fas fa-check text-success me-2"></i>User Type</li>
                                <li class="student-required"><i class="fas fa-check text-warning me-2"></i>Registration Number (Students)</li>
                            </ul>
                            
                            <h6 class="mt-3">Password Requirements</h6>
                            <ul class="small">
                                <li>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</li>
                                <li>Use a combination of letters and numbers</li>
                                <li>Avoid common passwords</li>
                            </ul>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Tip:</strong> Users can change their password after first login.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateUsername()">
                                    <i class="fas fa-user me-1"></i>Generate Username
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generatePassword()">
                                    <i class="fas fa-key me-1"></i>Generate Password
                                </button>
                                <a href="users.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-list me-1"></i>View All Users
                                </a>
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
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Handle user type change
document.querySelectorAll('input[name="user_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const isStudent = this.value === 'student';
        const studentYear = document.getElementById('student-year');
        const regNumber = document.getElementById('registration_number');
        const studentRequired = document.querySelectorAll('.student-required');
        
        if (isStudent) {
            studentYear.style.display = 'block';
            regNumber.required = true;
            studentRequired.forEach(el => el.style.display = 'inline');
        } else {
            studentYear.style.display = 'none';
            regNumber.required = false;
            studentRequired.forEach(el => el.style.display = 'none');
        }
    });
});

// Initialize user type display
document.addEventListener('DOMContentLoaded', function() {
    const checkedRadio = document.querySelector('input[name="user_type"]:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
});

// Generate username from full name
function generateUsername() {
    const fullName = document.getElementById('full_name').value.trim();
    if (fullName) {
        const names = fullName.toLowerCase().split(' ');
        let username = '';
        
        if (names.length >= 2) {
            username = names[0] + names[names.length - 1];
        } else {
            username = names[0];
        }
        
        // Remove special characters and add random number
        username = username.replace(/[^a-z0-9]/g, '') + Math.floor(Math.random() * 100);
        document.getElementById('username').value = username;
    } else {
        alert('Please enter the full name first.');
    }
}

// Generate random password
function generatePassword() {
    const password = '<?php echo generatePassword(8); ?>';
    document.getElementById('password').value = password;
    document.getElementById('confirm_password').value = password;
}

// Form validation
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const userType = document.querySelector('input[name="user_type"]:checked').value;
    const regNumber = document.getElementById('registration_number').value.trim();
    
    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        e.preventDefault();
        return;
    }
    
    if (userType === 'student' && !regNumber) {
        alert('Registration number is required for students.');
        e.preventDefault();
        return;
    }
});

// Auto-generate username when full name changes
document.getElementById('full_name').addEventListener('blur', function() {
    const usernameField = document.getElementById('username');
    if (!usernameField.value.trim()) {
        generateUsername();
    }
});
</script>
