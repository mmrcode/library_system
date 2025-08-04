<?php
/**
 * Analytics Dashboard - Library Management System
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

// Get analytics data
$analytics = [
    'overview' => $db->fetchRow("
        SELECT 
            (SELECT COUNT(*) FROM books WHERE status = 'active') as total_books,
            (SELECT COUNT(*) FROM users WHERE status = 'active' AND user_type != 'admin') as total_users,
            (SELECT COUNT(*) FROM book_issues WHERE status = 'issued') as active_issues,
            (SELECT COUNT(*) FROM book_issues WHERE status = 'overdue') as overdue_books,
            (SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE status = 'pending') as pending_fines
    "),
    
    'monthly_stats' => $db->fetchAll("
        SELECT 
            DATE_FORMAT(issue_date, '%Y-%m') as month,
            COUNT(*) as issues,
            COUNT(CASE WHEN return_date IS NOT NULL THEN 1 END) as returns
        FROM book_issues 
        WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
        ORDER BY month
    "),
    
    'popular_books' => $db->fetchAll("
        SELECT b.title, b.author, COUNT(bi.issue_id) as issue_count
        FROM books b
        JOIN book_issues bi ON b.book_id = bi.book_id
        GROUP BY b.book_id, b.title, b.author
        ORDER BY issue_count DESC
        LIMIT 10
    "),
    
    'user_activity' => $db->fetchAll("
        SELECT u.full_name, u.user_type, COUNT(bi.issue_id) as books_issued
        FROM users u
        JOIN book_issues bi ON u.user_id = bi.user_id
        WHERE u.user_type != 'admin'
        GROUP BY u.user_id, u.full_name, u.user_type
        ORDER BY books_issued DESC
        LIMIT 10
    "),
    
    'category_stats' => $db->fetchAll("
        SELECT c.category_name, 
               COUNT(b.book_id) as total_books,
               COUNT(bi.issue_id) as total_issues
        FROM categories c
        LEFT JOIN books b ON c.category_id = b.category_id
        LEFT JOIN book_issues bi ON b.book_id = bi.book_id
        GROUP BY c.category_id, c.category_name
        ORDER BY total_issues DESC
    "),
    
    'daily_activity' => $db->fetchAll("
        SELECT DATE(issue_date) as date, COUNT(*) as issues
        FROM book_issues 
        WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(issue_date)
        ORDER BY date
    ")
];

$pageTitle = 'Analytics Dashboard';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportAnalytics()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-lg-2-4 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?php echo number_format($analytics['overview']['total_books'] ?? 0); ?></h3>
                            <p class="mb-0">Total Books</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2-4 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo number_format($analytics['overview']['total_users'] ?? 0); ?></h3>
                            <p class="mb-0">Active Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2-4 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo number_format($analytics['overview']['active_issues'] ?? 0); ?></h3>
                            <p class="mb-0">Active Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2-4 col-md-6 mb-3">
                    <div class="card dashboard-card danger">
                        <div class="card-body text-center">
                            <h3 class="text-danger"><?php echo number_format($analytics['overview']['overdue_books'] ?? 0); ?></h3>
                            <p class="mb-0">Overdue Books</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2-4 col-md-6 mb-3">
                    <div class="card dashboard-card warning">
                        <div class="card-body text-center">
                            <h3 class="text-warning">₹<?php echo number_format($analytics['overview']['pending_fines'] ?? 0, 2); ?></h3>
                            <p class="mb-0">Pending Fines</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Monthly Activity Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-area me-2"></i>Monthly Activity Trend
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-pie me-2"></i>Category Distribution
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="row mb-4">
                <!-- Popular Books -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-star me-2"></i>Most Popular Books
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($analytics['popular_books'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Book</th>
                                                <th>Issues</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($analytics['popular_books'] as $index => $book): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($index < 3): ?>
                                                            <span class="badge bg-<?php echo ['warning', 'secondary', 'warning'][$index]; ?>">
                                                                <?php echo $index + 1; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?php echo $index + 1; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                        <small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $book['issue_count']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-users me-2"></i>Most Active Users
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($analytics['user_activity'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>User</th>
                                                <th>Books</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($analytics['user_activity'] as $index => $user): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($index < 3): ?>
                                                            <span class="badge bg-<?php echo ['warning', 'secondary', 'warning'][$index]; ?>">
                                                                <?php echo $index + 1; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?php echo $index + 1; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                        <span class="badge bg-<?php echo $user['user_type'] == 'faculty' ? 'primary' : 'info'; ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $user['books_issued']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Activity Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-bar me-2"></i>Daily Activity (Last 30 Days)
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyChart" width="400" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Activity Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("', '", array_column($analytics['monthly_stats'], 'month')) . "'"; ?>],
        datasets: [{
            label: 'Issues',
            data: [<?php echo implode(', ', array_column($analytics['monthly_stats'], 'issues')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Returns',
            data: [<?php echo implode(', ', array_column($analytics['monthly_stats'], 'returns')); ?>],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Issues vs Returns'
            }
        }
    }
});

// Category Distribution Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo "'" . implode("', '", array_column($analytics['category_stats'], 'category_name')) . "'"; ?>],
        datasets: [{
            data: [<?php echo implode(', ', array_column($analytics['category_stats'], 'total_issues')); ?>],
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384',
                '#C9CBCF'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Issues by Category'
            }
        }
    }
});

// Daily Activity Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo "'" . implode("', '", array_column($analytics['daily_activity'], 'date')) . "'"; ?>],
        datasets: [{
            label: 'Daily Issues',
            data: [<?php echo implode(', ', array_column($analytics['daily_activity'], 'issues')); ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Daily Book Issues'
            }
        }
    }
});

function refreshData() {
    location.reload();
}

function exportAnalytics() {
    alert('Analytics export functionality will be implemented');
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<style>
.col-lg-2-4 {
    flex: 0 0 20%;
    max-width: 20%;
}

@media (max-width: 992px) {
    .col-lg-2-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
</style>
