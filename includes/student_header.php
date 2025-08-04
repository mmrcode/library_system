<?php
/**
 * Student Header Template
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

$currentUser = getCurrentUser();
$libraryName = getSystemSetting('library_name', 'Digital Library');
$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($libraryName); ?> Student Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/student.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-book-reader me-2"></i>
                <span class="d-none d-md-inline ms-2"><?php echo htmlspecialchars($libraryName); ?> Student Portal</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 flex-wrap">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'search_books.php') ? 'active' : ''; ?>" href="search_books.php">
                            <i class="fas fa-search me-1"></i>Search Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_books.php') ? 'active' : ''; ?>" href="my_books.php">
                            <i class="fas fa-book me-1"></i>My Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'history.php') ? 'active' : ''; ?>" href="history.php">
                            <i class="fas fa-history me-1"></i>History
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto flex-wrap">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php 
                            $overdueCount = getStudentOverdueBooksCount($currentUser['user_id']);
                            if ($overdueCount > 0): 
                            ?>
                                <span class="badge bg-danger badge-pill d-none d-md-inline"><?php echo $overdueCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if ($overdueCount > 0): ?>
                                <li><a class="dropdown-item text-danger" href="my_books.php">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $overdueCount; ?> overdue book(s)
                                </a></li>
                            <?php endif; ?>
                            <?php
                            // Check for books due soon
                            $dueSoonCount = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE user_id = ? AND status = 'issued' AND DATEDIFF(due_date, CURDATE()) BETWEEN 0 AND 3", [$currentUser['user_id']]) ?? 0;
                            if ($dueSoonCount > 0):
                            ?>
                                <li><a class="dropdown-item text-warning" href="my_books.php">
                                    <i class="fas fa-clock me-2"></i><?php echo $dueSoonCount; ?> book(s) due soon
                                </a></li>
                            <?php endif; ?>
                            <?php if ($overdueCount == 0 && $dueSoonCount == 0): ?>
                                <li><span class="dropdown-item text-muted">No new notifications</span></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i><span class="d-none d-lg-inline ms-1"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">
                                <?php echo htmlspecialchars($currentUser['full_name']); ?><br>
                                <small class="text-muted">
                                    <?php echo !empty($currentUser['registration_number']) ? htmlspecialchars($currentUser['registration_number']) : 'Student'; ?>
                                </small>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Add top padding to account for fixed navbar -->
    <div style="padding-top: 20px;">
