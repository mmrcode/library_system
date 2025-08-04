<?php
/**
 * Admin Reports - Library Management System
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

// Get date range for reports
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
}

// Ensure start date is not after end date
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Get report data based on type
$reportData = [];

switch ($reportType) {
    case 'overview':
        $reportData = getOverviewReport($startDate, $endDate);
        break;
    case 'books':
        $reportData = getBooksReport($startDate, $endDate);
        break;
    case 'users':
        $reportData = getUsersReport($startDate, $endDate);
        break;
    case 'issues':
        $reportData = getIssuesReport($startDate, $endDate);
        break;
    case 'fines':
        $reportData = getFinesReport($startDate, $endDate);
        break;
    case 'categories':
        $reportData = getCategoriesReport($startDate, $endDate);
        break;
}

function getOverviewReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                (SELECT COUNT(*) FROM books WHERE status = 'active') as total_books,
                (SELECT COUNT(*) FROM users WHERE status = 'active' AND user_type != 'admin') as total_users,
                (SELECT COUNT(*) FROM book_issues WHERE issue_date BETWEEN ? AND ?) as period_issues,
                (SELECT COUNT(*) FROM book_issues WHERE status IN ('issued', 'overdue')) as active_issues,
                (SELECT COUNT(*) FROM book_issues WHERE status = 'overdue') as overdue_issues,
                (SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE status = 'pending') as pending_fines,
                (SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE status = 'paid' AND created_at BETWEEN ? AND ?) as collected_fines
        ", [$startDate, $endDate, $startDate, $endDate]),
        
        'daily_issues' => $db->fetchAll("
            SELECT DATE(issue_date) as date, COUNT(*) as count
            FROM book_issues 
            WHERE issue_date BETWEEN ? AND ?
            GROUP BY DATE(issue_date)
            ORDER BY date
        ", [$startDate, $endDate]),
        
        'category_distribution' => $db->fetchAll("
            SELECT c.category_name, COUNT(bi.issue_id) as issue_count
            FROM categories c
            LEFT JOIN books b ON c.category_id = b.category_id
            LEFT JOIN book_issues bi ON b.book_id = bi.book_id AND bi.issue_date BETWEEN ? AND ?
            GROUP BY c.category_id, c.category_name
            ORDER BY issue_count DESC
            LIMIT 10
        ", [$startDate, $endDate]),
        
        'top_books' => $db->fetchAll("
            SELECT b.title, b.author, COUNT(bi.issue_id) as issue_count
            FROM books b
            JOIN book_issues bi ON b.book_id = bi.book_id
            WHERE bi.issue_date BETWEEN ? AND ?
            GROUP BY b.book_id, b.title, b.author
            ORDER BY issue_count DESC
            LIMIT 10
        ", [$startDate, $endDate])
    ];
}

function getBooksReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                COUNT(*) as total_books,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_books,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_books,
                SUM(total_copies) as total_copies,
                SUM(available_copies) as available_copies,
                COUNT(CASE WHEN available_copies = 0 THEN 1 END) as out_of_stock
            FROM books
        "),
        
        'by_category' => $db->fetchAll("
            SELECT c.category_name, 
                   COUNT(b.book_id) as book_count,
                   SUM(b.total_copies) as total_copies,
                   SUM(b.available_copies) as available_copies
            FROM categories c
            LEFT JOIN books b ON c.category_id = b.category_id AND b.status = 'active'
            GROUP BY c.category_id, c.category_name
            ORDER BY book_count DESC
        "),
        
        'most_issued' => $db->fetchAll("
            SELECT b.title, b.author, b.isbn, c.category_name,
                   COUNT(bi.issue_id) as total_issues,
                   COUNT(CASE WHEN bi.issue_date BETWEEN ? AND ? THEN 1 END) as period_issues
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN book_issues bi ON b.book_id = bi.book_id
            GROUP BY b.book_id, b.title, b.author, b.isbn, c.category_name
            ORDER BY total_issues DESC
            LIMIT 20
        ", [$startDate, $endDate]),
        
        'low_stock' => $db->fetchAll("
            SELECT b.title, b.author, b.isbn, c.category_name,
                   b.total_copies, b.available_copies
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.category_id
            WHERE b.status = 'active' AND b.available_copies <= 2
            ORDER BY b.available_copies ASC, b.title
        ")
    ];
}

function getUsersReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users,
                COUNT(CASE WHEN user_type = 'student' THEN 1 END) as students,
                COUNT(CASE WHEN user_type = 'faculty' THEN 1 END) as faculty,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_registrations
            FROM users 
            WHERE user_type != 'admin'
        ", [$startDate, $endDate]),
        
        'by_department' => $db->fetchAll("
            SELECT department, 
                   COUNT(*) as user_count,
                   COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count
            FROM users 
            WHERE user_type != 'admin' AND department IS NOT NULL AND department != ''
            GROUP BY department
            ORDER BY user_count DESC
        "),
        
        'active_readers' => $db->fetchAll("
            SELECT u.full_name, u.registration_number, u.user_type, u.department,
                   COUNT(bi.issue_id) as total_issues,
                   COUNT(CASE WHEN bi.issue_date BETWEEN ? AND ? THEN 1 END) as period_issues,
                   COUNT(CASE WHEN bi.status IN ('issued', 'overdue') THEN 1 END) as current_issues
            FROM users u
            LEFT JOIN book_issues bi ON u.user_id = bi.user_id
            WHERE u.user_type != 'admin' AND u.status = 'active'
            GROUP BY u.user_id, u.full_name, u.registration_number, u.user_type, u.department
            HAVING total_issues > 0
            ORDER BY period_issues DESC, total_issues DESC
            LIMIT 20
        ", [$startDate, $endDate]),
        
        'defaulters' => $db->fetchAll("
            SELECT u.full_name, u.registration_number, u.user_type,
                   COUNT(bi.issue_id) as overdue_count,
                   COALESCE(SUM(f.fine_amount), 0) as total_fines
            FROM users u
            JOIN book_issues bi ON u.user_id = bi.user_id
            LEFT JOIN fines f ON bi.issue_id = f.issue_id AND f.status = 'pending'
            WHERE bi.status = 'overdue' AND u.status = 'active'
            GROUP BY u.user_id, u.full_name, u.registration_number, u.user_type
            ORDER BY overdue_count DESC, total_fines DESC
        ")
    ];
}

function getIssuesReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                COUNT(*) as total_issues,
                COUNT(CASE WHEN issue_date BETWEEN ? AND ? THEN 1 END) as period_issues,
                COUNT(CASE WHEN status = 'issued' THEN 1 END) as active_issues,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_issues,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_issues,
                AVG(CASE WHEN status = 'returned' THEN DATEDIFF(return_date, issue_date) END) as avg_reading_days
            FROM book_issues
        ", [$startDate, $endDate]),
        
        'monthly_trend' => $db->fetchAll("
            SELECT DATE_FORMAT(issue_date, '%Y-%m') as month,
                   COUNT(*) as issue_count,
                   COUNT(CASE WHEN status = 'returned' THEN 1 END) as return_count
            FROM book_issues
            WHERE issue_date >= DATE_SUB(?, INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
            ORDER BY month
        ", [$endDate]),
        
        'recent_issues' => $db->fetchAll("
            SELECT bi.issue_date, bi.due_date, bi.status,
                   u.full_name, u.registration_number,
                   b.title, b.author
            FROM book_issues bi
            JOIN users u ON bi.user_id = u.user_id
            JOIN books b ON bi.book_id = b.book_id
            WHERE bi.issue_date BETWEEN ? AND ?
            ORDER BY bi.issue_date DESC
            LIMIT 50
        ", [$startDate, $endDate]),
        
        'overdue_analysis' => $db->fetchAll("
            SELECT bi.issue_date, bi.due_date, 
                   DATEDIFF(CURDATE(), bi.due_date) as days_overdue,
                   u.full_name, u.registration_number,
                   b.title, b.author,
                   COALESCE(f.fine_amount, 0) as fine_amount
            FROM book_issues bi
            JOIN users u ON bi.user_id = u.user_id
            JOIN books b ON bi.book_id = b.book_id
            LEFT JOIN fines f ON bi.issue_id = f.issue_id
            WHERE bi.status = 'overdue'
            ORDER BY days_overdue DESC
        ")
    ];
}

function getFinesReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                COUNT(*) as total_fines,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as period_fines,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_fines,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_fines,
                COUNT(CASE WHEN status = 'waived' THEN 1 END) as waived_fines,
                COALESCE(SUM(fine_amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN fine_amount END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = 'paid' AND created_at BETWEEN ? AND ? THEN fine_amount END), 0) as collected_amount
            FROM fines
        ", [$startDate, $endDate, $startDate, $endDate]),
        
        'monthly_collection' => $db->fetchAll("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                   COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                   COALESCE(SUM(CASE WHEN status = 'paid' THEN fine_amount END), 0) as collected_amount
            FROM fines
            WHERE created_at >= DATE_SUB(?, INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ", [$endDate]),
        
        'top_defaulters' => $db->fetchAll("
            SELECT u.full_name, u.registration_number, u.user_type,
                   COUNT(f.fine_id) as fine_count,
                   COALESCE(SUM(f.fine_amount), 0) as total_fine_amount,
                   COALESCE(SUM(CASE WHEN f.status = 'pending' THEN f.fine_amount END), 0) as pending_amount
            FROM users u
            JOIN book_issues bi ON u.user_id = bi.user_id
            JOIN fines f ON bi.issue_id = f.issue_id
            WHERE f.created_at BETWEEN ? AND ?
            GROUP BY u.user_id, u.full_name, u.registration_number, u.user_type
            ORDER BY total_fine_amount DESC
            LIMIT 20
        ", [$startDate, $endDate]),
        
        'recent_fines' => $db->fetchAll("
            SELECT f.created_at, f.fine_amount, f.status, f.reason,
                   u.full_name, u.registration_number,
                   b.title, b.author
            FROM fines f
            JOIN book_issues bi ON f.issue_id = bi.issue_id
            JOIN users u ON bi.user_id = u.user_id
            JOIN books b ON bi.book_id = b.book_id
            WHERE f.created_at BETWEEN ? AND ?
            ORDER BY f.created_at DESC
            LIMIT 50
        ", [$startDate, $endDate])
    ];
}

function getCategoriesReport($startDate, $endDate) {
    global $db;
    
    return [
        'summary' => $db->fetchRow("
            SELECT 
                COUNT(*) as total_categories,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_categories
            FROM categories
        "),
        
        'category_performance' => $db->fetchAll("
            SELECT c.category_name,
                   COUNT(DISTINCT b.book_id) as book_count,
                   COUNT(bi.issue_id) as total_issues,
                   COUNT(CASE WHEN bi.issue_date BETWEEN ? AND ? THEN 1 END) as period_issues,
                   COUNT(CASE WHEN bi.status IN ('issued', 'overdue') THEN 1 END) as active_issues,
                   AVG(CASE WHEN bi.status = 'returned' THEN DATEDIFF(bi.return_date, bi.issue_date) END) as avg_reading_days
            FROM categories c
            LEFT JOIN books b ON c.category_id = b.category_id AND b.status = 'active'
            LEFT JOIN book_issues bi ON b.book_id = bi.book_id
            GROUP BY c.category_id, c.category_name
            ORDER BY period_issues DESC
        ", [$startDate, $endDate]),
        
        'popular_books_by_category' => $db->fetchAll("
            SELECT c.category_name, b.title, b.author,
                   COUNT(bi.issue_id) as issue_count
            FROM categories c
            JOIN books b ON c.category_id = b.category_id
            JOIN book_issues bi ON b.book_id = bi.book_id
            WHERE bi.issue_date BETWEEN ? AND ?
            GROUP BY c.category_id, c.category_name, b.book_id, b.title, b.author
            ORDER BY c.category_name, issue_count DESC
        ", [$startDate, $endDate])
    ];
}

$pageTitle = 'Reports & Analytics';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print Report
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportReport('csv')">
                            <i class="fas fa-file-csv me-1"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Report Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="overview" <?php echo ($reportType == 'overview') ? 'selected' : ''; ?>>Overview</option>
                                <option value="books" <?php echo ($reportType == 'books') ? 'selected' : ''; ?>>Books Report</option>
                                <option value="users" <?php echo ($reportType == 'users') ? 'selected' : ''; ?>>Users Report</option>
                                <option value="issues" <?php echo ($reportType == 'issues') ? 'selected' : ''; ?>>Issues Report</option>
                                <option value="fines" <?php echo ($reportType == 'fines') ? 'selected' : ''; ?>>Fines Report</option>
                                <option value="categories" <?php echo ($reportType == 'categories') ? 'selected' : ''; ?>>Categories Report</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-line me-1"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Content -->
            <div id="reportContent">
                <?php
                switch ($reportType) {
                    case 'overview':
                        include 'reports/overview.php';
                        break;
                    case 'books':
                        include 'reports/books.php';
                        break;
                    case 'users':
                        include 'reports/users.php';
                        break;
                    case 'issues':
                        include 'reports/issues.php';
                        break;
                    case 'fines':
                        include 'reports/fines.php';
                        break;
                    case 'categories':
                        include 'reports/categories.php';
                        break;
                }
                ?>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
// Auto-submit form on report type change
document.getElementById('report_type').addEventListener('change', function() {
    this.form.submit();
});

// Export functions
function exportReport(format) {
    const reportType = '<?php echo $reportType; ?>';
    const startDate = '<?php echo $startDate; ?>';
    const endDate = '<?php echo $endDate; ?>';
    
    const url = `export_report.php?format=${format}&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}

// Print styles
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>

<style>
@media print {
    .btn-toolbar, .card-header .btn, nav, .sidebar {
        display: none !important;
    }
    
    .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    main {
        margin: 0 !important;
        padding: 20px !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    
    .page-break {
        page-break-before: always;
    }
}
</style>
