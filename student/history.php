<?php
/**
 * Student Reading History - Library Management System
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
require_once '../includes/thumbnail_generator.php';

// Require student access
requireStudent();

$db = Database::getInstance();
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Get filter parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query conditions
$whereConditions = ["bi.user_id = ?"];
$params = [$userId];

// Year filter
$whereConditions[] = "YEAR(bi.issue_date) = ?";
$params[] = $year;

// Month filter
if ($month > 0) {
    $whereConditions[] = "MONTH(bi.issue_date) = ?";
    $params[] = $month;
}

// Category filter
if ($category > 0) {
    $whereConditions[] = "b.category_id = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$totalRecords = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM book_issues bi 
    JOIN books b ON bi.book_id = b.book_id
    WHERE {$whereClause}
", $params) ?? 0;

$totalPages = ceil($totalRecords / $limit);

// Get reading history
$history = $db->fetchAll("
    SELECT bi.*, b.title, b.author, b.isbn, b.publisher,
           c.category_name,
           DATEDIFF(COALESCE(bi.return_date, CURDATE()), bi.issue_date) as reading_days,
           f.fine_amount
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    LEFT JOIN categories c ON b.category_id = c.category_id
    LEFT JOIN fines f ON bi.issue_id = f.issue_id
    WHERE {$whereClause}
    ORDER BY bi.issue_date DESC
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Get available years for filter
$availableYears = $db->fetchAll("
    SELECT DISTINCT YEAR(issue_date) as year 
    FROM book_issues 
    WHERE user_id = ? 
    ORDER BY year DESC
", [$userId]);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");

// Get reading statistics for the selected period
$stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total_books,
        COUNT(CASE WHEN bi.status = 'returned' THEN 1 END) as completed_books,
        COUNT(CASE WHEN bi.status IN ('issued', 'overdue') THEN 1 END) as current_books,
        COUNT(DISTINCT b.category_id) as categories_read,
        AVG(DATEDIFF(COALESCE(return_date, CURDATE()), issue_date)) as avg_reading_days,
        SUM(COALESCE(f.fine_amount, 0)) as total_fines
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    LEFT JOIN fines f ON bi.issue_id = f.issue_id
    WHERE {$whereClause}
", $params);

$pageTitle = 'Reading History';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-history me-2"></i>Reading History
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="my_books.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-book me-1"></i>Current Books
                        </a>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <?php foreach ($availableYears as $yearOption): ?>
                                    <option value="<?php echo $yearOption['year']; ?>" 
                                            <?php echo ($year == $yearOption['year']) ? 'selected' : ''; ?>>
                                        <?php echo $yearOption['year']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month">
                                <option value="0" <?php echo ($month == 0) ? 'selected' : ''; ?>>All Months</option>
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($months as $num => $name):
                                ?>
                                    <option value="<?php echo $num; ?>" <?php echo ($month == $num) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="0" <?php echo ($category == 0) ? 'selected' : ''; ?>>All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                            <?php echo ($category == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-primary"><?php echo $stats['total_books'] ?? 0; ?></h4>
                            <small>Total Books</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-success"><?php echo $stats['completed_books'] ?? 0; ?></h4>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-info"><?php echo $stats['current_books'] ?? 0; ?></h4>
                            <small>Current</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-warning"><?php echo $stats['categories_read'] ?? 0; ?></h4>
                            <small>Categories</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-secondary"><?php echo round($stats['avg_reading_days'] ?? 0); ?></h4>
                            <small>Avg Days</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card dashboard-card text-center">
                        <div class="card-body">
                            <h4 class="text-danger">₹<?php echo number_format($stats['total_fines'] ?? 0, 2); ?></h4>
                            <small>Total Fines</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Reading History
                        <?php if ($month > 0): ?>
                            - <?php echo $months[$month]; ?> <?php echo $year; ?>
                        <?php else: ?>
                            - <?php echo $year; ?>
                        <?php endif; ?>
                        (<?php echo $totalRecords; ?> records)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($history)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Details</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Reading Days</th>
                                        <th>Status</th>
                                        <th>Fine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo generateBookThumbnailFromData($record, 'small'); ?>" 
                                                         class="me-3 rounded" 
                                                         style="width: 40px; height: 50px; object-fit: cover;" 
                                                         alt="Book Cover">
                                                    <div>
                                                        <h6 class="mb-1 small"><?php echo htmlspecialchars($record['title']); ?></h6>
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($record['author']); ?>
                                                            <?php if (!empty($record['category_name'])): ?>
                                                                <br><span class="badge bg-light text-dark"><?php echo htmlspecialchars($record['category_name']); ?></span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatDate($record['issue_date']); ?></td>
                                            <td>
                                                <?php if ($record['return_date']): ?>
                                                    <?php echo formatDate($record['return_date']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not returned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $record['reading_days']; ?> days</span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = 'secondary';
                                                $statusText = ucfirst($record['status']);
                                                switch ($record['status']) {
                                                    case 'returned':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'issued':
                                                        $statusClass = 'primary';
                                                        break;
                                                    case 'overdue':
                                                        $statusClass = 'danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($record['fine_amount'])): ?>
                                                    <span class="text-danger">₹<?php echo number_format($record['fine_amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#historyDetailsModal"
                                                        onclick="showHistoryDetails(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="History pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No History -->
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No reading history found</h4>
                            <p class="text-muted">
                                <?php if ($month > 0 || $category > 0): ?>
                                    Try adjusting your filters or 
                                    <a href="history.php?year=<?php echo $year; ?>">view all records for <?php echo $year; ?></a>.
                                <?php else: ?>
                                    Start reading books to build your reading history!
                                    <br><a href="search_books.php">Search for books</a> to get started.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- History Details Modal -->
<div class="modal fade" id="historyDetailsModal" tabindex="-1" aria-labelledby="historyDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyDetailsModalLabel">Reading History Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div id="modalHistoryNameDisplay" class="book-name-display d-flex align-items-center justify-content-center text-center">
                            <h3 id="modalHistoryNameTitle" class="book-title-display"></h3>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 id="modalHistoryTitle"></h4>
                        <p class="text-muted mb-3" id="modalHistoryAuthor"></p>
                        
                        <table class="table table-sm">
                            <tr>
                                <th>ISBN:</th>
                                <td id="modalHistoryISBN"></td>
                            </tr>
                            <tr>
                                <th>Publisher:</th>
                                <td id="modalHistoryPublisher"></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td id="modalHistoryCategory"></td>
                            </tr>
                            <tr>
                                <th>Issue Date:</th>
                                <td id="modalHistoryIssueDate"></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td id="modalHistoryDueDate"></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td id="modalHistoryReturnDate"></td>
                            </tr>
                            <tr>
                                <th>Reading Duration:</th>
                                <td id="modalHistoryDuration"></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="modalHistoryStatus"></td>
                            </tr>
                            <tr>
                                <th>Fine Amount:</th>
                                <td id="modalHistoryFine"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>

<style>
.book-name-display {
    height: 300px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    border: 3px solid #f8f9fa;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.book-title-display {
    color: white;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    word-wrap: break-word;
    line-height: 1.2;
    margin: 0;
    padding: 20px;
}
</style>

<script>
function showHistoryDetails(record) {
    // Set book name in the display area
    document.getElementById('modalHistoryNameTitle').textContent = record.title;
    
    // Set book details
    document.getElementById('modalHistoryTitle').textContent = record.title;
    document.getElementById('modalHistoryAuthor').textContent = 'by ' + record.author;
    document.getElementById('modalHistoryISBN').textContent = record.isbn;
    document.getElementById('modalHistoryPublisher').textContent = record.publisher;
    document.getElementById('modalHistoryCategory').textContent = record.category_name || 'Uncategorized';
    document.getElementById('modalHistoryIssueDate').textContent = formatDate(record.issue_date);
    document.getElementById('modalHistoryDueDate').textContent = formatDate(record.due_date);
    
    // Return date
    const returnDateElement = document.getElementById('modalHistoryReturnDate');
    if (record.return_date) {
        returnDateElement.textContent = formatDate(record.return_date);
    } else {
        returnDateElement.textContent = 'Not returned yet';
    }
    
    // Duration
    document.getElementById('modalHistoryDuration').textContent = record.reading_days + ' days';
    
    // Status
    const statusElement = document.getElementById('modalHistoryStatus');
    let statusBadge = '';
    switch (record.status) {
        case 'returned':
            statusBadge = '<span class="badge bg-success">Returned</span>';
            break;
        case 'issued':
            statusBadge = '<span class="badge bg-primary">Issued</span>';
            break;
        case 'overdue':
            statusBadge = '<span class="badge bg-danger">Overdue</span>';
            break;
        default:
            statusBadge = '<span class="badge bg-secondary">' + record.status + '</span>';
    }
    statusElement.innerHTML = statusBadge;
    
    // Fine amount
    const fineElement = document.getElementById('modalHistoryFine');
    if (record.fine_amount && record.fine_amount > 0) {
        fineElement.innerHTML = '<span class="text-danger">₹' + parseFloat(record.fine_amount).toFixed(2) + '</span>';
    } else {
        fineElement.innerHTML = '<span class="text-success">No fine</span>';
    }
}



// Auto-submit form on filter change
document.getElementById('year').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('month').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});
</script>
