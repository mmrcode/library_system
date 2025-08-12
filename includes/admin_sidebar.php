<?php
/**
 * Admin Sidebar Navigation
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

// Get current page name for active menu highlighting
// tiny UX touch: highlights the active menu item so admin knows where they are
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Book Management</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'books.php') ? 'active' : ''; ?>" href="books.php">
                    <i class="fas fa-book"></i>
                    All Books
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'add_book.php') ? 'active' : ''; ?>" href="add_book.php">
                    <i class="fas fa-plus-circle"></i>
                    Add New Book
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>User Management</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    All Users
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'add_user.php') ? 'active' : ''; ?>" href="add_user.php">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Issue & Return</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'issue_book.php') ? 'active' : ''; ?>" href="issue_book.php">
                    <i class="fas fa-hand-holding"></i>
                    Issue Book
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'return_book.php') ? 'active' : ''; ?>" href="return_book.php">
                    <i class="fas fa-undo"></i>
                    Return Book
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'all_issues.php') ? 'active' : ''; ?>" href="all_issues.php">
                    <i class="fas fa-list"></i>
                    All Issues
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'overdue_books.php') ? 'active' : ''; ?>" href="overdue_books.php">
                    <i class="fas fa-exclamation-triangle"></i>
                    Overdue Books
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Fines & Payments</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'manage_fines.php') ? 'active' : ''; ?>" href="manage_fines.php">
                    <i class="fas fa-rupee-sign"></i>
                    Manage Fines
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Reports</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    Generate Reports
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>" href="analytics.php">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </li>
            
            <li class="nav-item">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>System</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'activity_logs.php') ? 'active' : ''; ?>" href="activity_logs.php">
                    <i class="fas fa-history"></i>
                    Activity Logs
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'backup.php') ? 'active' : ''; ?>" href="backup.php">
                    <i class="fas fa-database"></i>
                    Backup & Restore
                </a>
            </li>
        </ul>
        
        <!-- Quick Stats in Sidebar -->
        <!-- quick glance numbers; not super accurate in realtime but good enough for nav -->
        <div class="mt-4 px-3">
            <div class="card bg-light border-0">
                <div class="card-body p-3">
                    <h6 class="card-title text-muted">Quick Stats</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary">
                                <i class="fas fa-book"></i>
                                <div class="small"><?php 
                                    $totalBooks = fetchOne("SELECT COUNT(*) as count FROM books WHERE status = 'active'")['count'] ?? 0;
                                    echo number_format($totalBooks);
                                ?></div>
                                <div class="text-muted small">Books</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="fas fa-users"></i>
                                <div class="small"><?php 
                                    $totalUsers = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'")['count'] ?? 0;
                                    echo number_format($totalUsers);
                                ?></div>
                                <div class="text-muted small">Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-6">
                            <div class="text-warning">
                                <i class="fas fa-exchange-alt"></i>
                                <div class="small"><?php 
                                    $activeIssues = fetchOne("SELECT COUNT(*) as count FROM book_issues WHERE status = 'issued'")['count'] ?? 0;
                                    echo number_format($activeIssues);
                                ?></div>
                                <div class="text-muted small">Issued</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="small"><?php 
                                    $overdueBooks = fetchOne("SELECT COUNT(*) as count FROM book_issues WHERE status = 'overdue'")['count'] ?? 0;
                                    echo number_format($overdueBooks);
                                ?></div>
                                <div class="text-muted small">Overdue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <!-- version is pulled from APP_VERSION constant; just a nice touch for debugging -->
        <div class="mt-3 px-3">
            <div class="card bg-primary text-white border-0">
                <div class="card-body p-3 text-center">
                    <h6 class="card-title">System Info</h6>
                    <small>Version: <?php echo APP_VERSION; ?></small><br>
                    <small>Last Login: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></small>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.sidebar { /* fixed sidebar with gradient background */
    position: fixed;
    top: 76px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
    width: 280px;
    overflow-y: auto;
}

.sidebar .nav-link { /* default link style; we nudge color on hover */
    color: rgba(255, 255, 255, 0.8);
    padding: 0.75rem 1.5rem;
    border-radius: 0;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
    border-left-color: white;
}

.sidebar .nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 0.75rem;
}

.sidebar-heading {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.6) !important;
    letter-spacing: 0.05em;
}

@media (max-width: 768px) { /* slide-in on small screens */
    .sidebar {
        position: fixed;
        top: 76px;
        left: -280px;
        transition: left 0.3s ease;
    }
    
    .sidebar.show {
        left: 0;
    }
}
</style>

<script>
// Sidebar toggle for mobile
// vanilla JS, no jQuery needed; keeps bundle small
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});
</script>
