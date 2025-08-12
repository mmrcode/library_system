<?php
/**
 * Admin Header Template
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

$currentUser = getCurrentUser(); // pulling the logged-in admin for navbar
$libraryName = getSystemSetting('library_name', 'Digital Library'); // fallback just in case settings table is empty
// NOTE: keeping header minimal so pages load fast; heavy stuff goes in pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($libraryName); ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <!-- CDN is fine for our use case; avoids bundling headaches -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .main-content { /* content wrapper for pages (paired with sidebar) */
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3 d-md-none" type="button" id="sidebarToggle"> <!-- sidebar toggler for mobile -->
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book-open me-2"></i>
                <?php echo htmlspecialchars($libraryName); ?> Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <!-- Notifications Dropdown -->
                <!-- Placeholder counts/icons for now; can wire to real notifications later -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger badge-pill">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i>5 books overdue</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-book text-info me-2"></i>Low stock alert</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user text-success me-2"></i>New user registered</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                    </ul>
                </div>
                
                <!-- User Dropdown -->
                <!-- shows current admin name + quick links -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></h6></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Add top padding to account for fixed navbar -->
    <div style="padding-top: 76px;">
