<?php
/**
 * Admin Dashboard - Library Management System
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

// Update overdue books status
updateOverdueBooks();

// Get dashboard statistics
$db = Database::getInstance();

// Total books
$totalBooks = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE status = 'active'") ?? 0;

// Total users (students)
$totalUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'active'") ?? 0;

// Active issues
$activeIssues = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE status = 'issued'") ?? 0;

// Overdue books
$overdueBooks = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE status = 'overdue'") ?? 0;

// Total fines pending
$totalFines = $db->fetchColumn("SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE status = 'pending'") ?? 0;

// Available books
$availableBooks = $db->fetchColumn("SELECT SUM(available_copies) FROM books WHERE status = 'active'") ?? 0;

// Recent activities
$recentActivities = $db->fetchAll("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.user_id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Recent book issues
$recentIssues = $db->fetchAll("
    SELECT bi.*, b.title, b.author, u.full_name, u.registration_number
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    JOIN users u ON bi.user_id = u.user_id
    ORDER BY bi.created_at DESC
    LIMIT 5
");

// Books with low stock
$lowStockBooks = $db->fetchAll("
    SELECT book_id, title, author, total_copies, available_copies
    FROM books 
    WHERE status = 'active' AND available_copies <= 2 AND available_copies > 0
    ORDER BY available_copies ASC
    LIMIT 5
");

$pageTitle = 'Admin Dashboard';
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
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Quick Add
                    </button>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Books</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo number_format($totalBooks); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-book fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card success">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Students</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo number_format($totalUsers); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card warning">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Active Issues</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo number_format($activeIssues); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exchange-alt fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card danger">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Overdue Books</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo number_format($overdueBooks); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Statistics Row -->
            <div class="row mb-4">
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card dashboard-card info">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Available Books</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo number_format($availableBooks); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card dashboard-card" style="background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%);">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Fines</div>
                                    <div class="h5 mb-0 font-weight-bold">₹<?php echo number_format($totalFines, 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-rupee-sign fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Recent Book Issues -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-list me-2"></i>Recent Book Issues
                            </h6>
                            <a href="issues.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentIssues)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Student</th>
                                                <th>Issue Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentIssues as $issue): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($issue['title']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($issue['author']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($issue['full_name']); ?><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($issue['registration_number']); ?></small>
                                                    </td>
                                                    <td><?php echo formatDate($issue['issue_date']); ?></td>
                                                    <td><?php echo getIssueStatusBadge($issue['status']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No recent book issues found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Books -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                            </h6>
                            <a href="books.php" class="btn btn-sm btn-warning">Manage Books</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lowStockBooks)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Available</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockBooks as $book): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?php echo $book['available_copies']; ?></span>
                                                    </td>
                                                    <td><?php echo $book['total_copies']; ?></td>
                                                    <td><?php echo getBookStatusBadge($book['available_copies'], $book['total_copies']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">All books are well stocked!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-history me-2"></i>Recent Activities
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentActivities)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Table</th>
                                                <th>Time</th>
                                                <th>IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivities as $activity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($activity['action']); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($activity['table_name'] ?? '-'); ?></td>
                                                    <td><?php echo formatDateTime($activity['created_at']); ?></td>
                                                    <td>
                                                        <small class="text-muted"><?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No recent activities found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
// Auto-refresh dashboard every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>
