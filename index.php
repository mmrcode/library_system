<?php
/**
 * Main Landing Page - Library Management System
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
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $userType = $_SESSION['user_type'];
    if ($userType === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

// Handle login form submission
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $loginError = 'Please enter both username and password';
    } else {
        $auth = new Auth();
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit();
        } else {
            $loginError = $result['message'];
        }
    }
}

// Get system settings for display
$libraryName = getSystemSetting('library_name', 'Digital Library');
$workingHours = getSystemSetting('working_hours', '9:00 AM - 6:00 PM');
$libraryEmail = getSystemSetting('library_email', 'library@university.edu');
$libraryPhone = getSystemSetting('library_phone', '+91-XXXXXXXXXX');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($libraryName); ?> - Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book-open me-2"></i>
                <?php echo htmlspecialchars($libraryName); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold text-primary mb-4">
                            Welcome to <?php echo htmlspecialchars($libraryName); ?>
                        </h1>
                        <p class="lead text-muted mb-4">
                            A comprehensive digital library management system designed to streamline 
                            book management, user registration, and automated tracking of book issues and returns.
                        </p>
                        <div class="d-flex gap-3">
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            <a href="#features" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <i class="fas fa-book-reader display-1 text-primary opacity-75"></i>
                        <div class="mt-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">Quick Stats</h5>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <h4 class="text-success"><?php 
                                                $totalBooks = fetchOne("SELECT COUNT(*) as count FROM books WHERE status = 'active'")['count'] ?? 0;
                                                echo number_format($totalBooks);
                                            ?></h4>
                                            <small class="text-muted">Books</small>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-info"><?php 
                                                $totalUsers = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'")['count'] ?? 0;
                                                echo number_format($totalUsers);
                                            ?></h4>
                                            <small class="text-muted">Students</small>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-warning"><?php 
                                                $activeIssues = fetchOne("SELECT COUNT(*) as count FROM book_issues WHERE status = 'issued'")['count'] ?? 0;
                                                echo number_format($activeIssues);
                                            ?></h4>
                                            <small class="text-muted">Active Issues</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">System Features</h2>
                    <p class="lead text-muted">Comprehensive library management made simple</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">User Management</h5>
                            <p class="card-text text-muted">
                                Secure registration and management of students and faculty members 
                                with role-based access control.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-book fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Book Management</h5>
                            <p class="card-text text-muted">
                                Complete book inventory management with categories, search functionality, 
                                and real-time availability tracking.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Issue & Return</h5>
                            <p class="card-text text-muted">
                                Streamlined book issue and return process with automated 
                                due date calculation and tracking.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-calculator fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Fine Management</h5>
                            <p class="card-text text-muted">
                                Automatic fine calculation for overdue books with 
                                payment tracking and reporting.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-search fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">Advanced Search</h5>
                            <p class="card-text text-muted">
                                Powerful search functionality by title, author, category, 
                                and ISBN with filtering options.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-chart-bar fa-3x text-secondary"></i>
                            </div>
                            <h5 class="card-title">Reports & Analytics</h5>
                            <p class="card-text text-muted">
                                Comprehensive reporting with export capabilities 
                                for better library administration.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold text-primary mb-4">About the System</h2>
                    <p class="lead text-muted mb-4">
                        This Library Management System is developed as part of the Bachelor of Computer Applications (BCA) 
                        curriculum at the University of Mysore, designed to modernize library operations.
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Web-based Interface</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Secure Authentication</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Real-time Updates</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span>Mobile Responsive</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Project Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Student:</strong></td>
                                    <td>Mohammad Muqsit Raja</td>
                                </tr>
                                <tr>
                                    <td><strong>Registration No:</strong></td>
                                    <td>BCA22739</td>
                                </tr>
                                <tr>
                                    <td><strong>University:</strong></td>
                                    <td>University of Mysore</td>
                                </tr>
                                <tr>
                                    <td><strong>Year:</strong></td>
                                    <td>2025</td>
                                </tr>
                                <tr>
                                    <td><strong>Technology:</strong></td>
                                    <td>PHP, MySQL, Bootstrap</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">Contact Information</h2>
                    <p class="lead text-muted">Get in touch with the library administration</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="contact-card card h-100 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h5>Working Hours</h5>
                            <p class="text-muted"><?php echo htmlspecialchars($workingHours); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card card h-100 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fas fa-envelope fa-3x text-success mb-3"></i>
                            <h5>Email</h5>
                            <p class="text-muted">mmrcode1@gmail.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card card h-100 shadow-sm text-center">
                        <div class="card-body p-4">
                            <i class="fab fa-github fa-3x text-dark mb-3"></i>
                            <h5>GitHub</h5>
                            <p class="text-muted"><a href="https://github.com/mmrcode" target="_blank" class="text-decoration-none">github.com/mmrcode</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Library System
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (!empty($loginError)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($loginError); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="alert alert-info">
                                <h6 class="mb-2"><i class="fas fa-users me-2"></i>Login Credentials:</h6>
                                <div class="row text-start">
                                    <div class="col-md-6">
                                        <strong class="text-primary">Librarian Access:</strong><br>
                                        <small>Username: <code>admin</code><br>
                                        Password: <code>admin123</code></small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong class="text-success">Student Access:</strong><br>
                                        <small>Username: <code>student1</code><br>
                                        Password: <code>password123</code></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="login" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-primary text-white py-4">
        <div class="container">
            <div class="text-center">
                <h5 class="mb-2">University Library</h5>
                <p class="text-light mb-2">Digital Library Management System</p>
                <p class="text-light mb-0">
                    © 2025 Library Management System - Mohammad Muqsit Raja
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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

        // Show login modal if there's an error
        <?php if (!empty($loginError)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        });
        <?php endif; ?>

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
