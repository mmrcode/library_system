<?php
/**
 * All Issues - Library Management System
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
require_once '../includes/thumbnail_generator.php';

// Require admin access
requireAdmin();

$db = Database::getInstance();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query conditions
$whereConditions = ['1=1'];
$params = [];

if ($status !== 'all') {
    $whereConditions[] = "bi.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.registration_number LIKE ? OR b.title LIKE ? OR b.author LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$totalIssues = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM book_issues bi
    JOIN users u ON bi.user_id = u.user_id
    JOIN books b ON bi.book_id = b.book_id
    WHERE {$whereClause}
", $params) ?? 0;

$totalPages = ceil($totalIssues / $limit);

// Get issues
$issues = $db->fetchAll("
    SELECT bi.*, u.full_name, u.registration_number, u.user_type,
           b.title, b.author, b.isbn, c.category_name,
           DATEDIFF(bi.due_date, CURDATE()) as days_remaining,
           f.fine_amount
    FROM book_issues bi
    JOIN users u ON bi.user_id = u.user_id
    JOIN books b ON bi.book_id = b.book_id
    LEFT JOIN categories c ON b.category_id = c.category_id
    LEFT JOIN fines f ON bi.issue_id = f.issue_id AND f.status = 'pending'
    WHERE {$whereClause}
    ORDER BY bi.issue_date DESC
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Get summary statistics
$stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total_issues,
        COUNT(CASE WHEN status = 'issued' THEN 1 END) as active_issues,
        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_issues,
        COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_issues
    FROM book_issues
");

$pageTitle = 'All Issues';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-exchange-alt me-2"></i>All Issues
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="issue_book.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Issue Book
                        </a>
                        <a href="return_book.php" class="btn btn-sm btn-success">
                            <i class="fas fa-undo me-1"></i>Return Book
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?php echo number_format($stats['total_issues'] ?? 0); ?></h3>
                            <p class="mb-0">Total Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo number_format($stats['active_issues'] ?? 0); ?></h3>
                            <p class="mb-0">Active Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card danger">
                        <div class="card-body text-center">
                            <h3 class="text-danger"><?php echo number_format($stats['overdue_issues'] ?? 0); ?></h3>
                            <p class="mb-0">Overdue Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card success">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo number_format($stats['returned_issues'] ?? 0); ?></h3>
                            <p class="mb-0">Returned</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="User name, registration, book title, author">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                                <option value="issued" <?php echo ($status == 'issued') ? 'selected' : ''; ?>>Active</option>
                                <option value="overdue" <?php echo ($status == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                <option value="returned" <?php echo ($status == 'returned') ? 'selected' : ''; ?>>Returned</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="all_issues.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Issues Table -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Book Issues 
                        <?php if ($totalIssues > 0): ?>
                            (<?php echo number_format($totalIssues); ?> records)
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($issues)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Issue Date</th>
                                        <th>User</th>
                                        <th>Book</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Fine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issues as $issue): ?>
                                        <tr>
                                            <td><?php echo formatDate($issue['issue_date']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($issue['full_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($issue['registration_number']); ?>
                                                    <span class="badge bg-<?php echo $issue['user_type'] == 'faculty' ? 'primary' : 'info'; ?> ms-1">
                                                        <?php echo ucfirst($issue['user_type']); ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo generateBookThumbnailFromData($issue, 'small'); ?>" 
                                                         class="me-3 rounded" 
                                                         style="width: 40px; height: 50px; object-fit: cover;" 
                                                         alt="Book Cover">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($issue['title']); ?></strong><br>
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($issue['author']); ?>
                                                            <?php if (!empty($issue['category_name'])): ?>
                                                                | <?php echo htmlspecialchars($issue['category_name']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo formatDate($issue['due_date']); ?>
                                                <?php if ($issue['status'] !== 'returned'): ?>
                                                    <?php if ($issue['days_remaining'] < 0): ?>
                                                        <br><small class="text-danger">
                                                            <?php echo abs($issue['days_remaining']); ?> days overdue
                                                        </small>
                                                    <?php elseif ($issue['days_remaining'] <= 3): ?>
                                                        <br><small class="text-warning">
                                                            <?php echo $issue['days_remaining']; ?> days remaining
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = 'secondary';
                                                $statusText = ucfirst($issue['status']);
                                                switch ($issue['status']) {
                                                    case 'issued':
                                                        if ($issue['days_remaining'] < 0) {
                                                            $badgeClass = 'danger';
                                                            $statusText = 'Overdue';
                                                        } elseif ($issue['days_remaining'] <= 3) {
                                                            $badgeClass = 'warning';
                                                            $statusText = 'Due Soon';
                                                        } else {
                                                            $badgeClass = 'primary';
                                                            $statusText = 'Active';
                                                        }
                                                        break;
                                                    case 'overdue':
                                                        $badgeClass = 'danger';
                                                        break;
                                                    case 'returned':
                                                        $badgeClass = 'success';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($issue['fine_amount'])): ?>
                                                    <span class="text-danger">₹<?php echo number_format($issue['fine_amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#issueDetailsModal"
                                                            onclick="showIssueDetails(<?php echo htmlspecialchars(json_encode($issue)); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($issue['status'] !== 'returned'): ?>
                                                        <a href="return_book.php?issue_id=<?php echo $issue['issue_id']; ?>" 
                                                           class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Issues pagination" class="mt-4">
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
                        <div class="text-center py-4">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No issues found</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || $status !== 'all'): ?>
                                    Try adjusting your search criteria.
                                <?php else: ?>
                                    No book issues have been recorded yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Issue Details Modal -->
<div class="modal fade" id="issueDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>User Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Name:</th>
                                <td id="modal_user_name"></td>
                            </tr>
                            <tr>
                                <th>Registration:</th>
                                <td id="modal_user_reg"></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td id="modal_user_type"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Book Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Title:</th>
                                <td id="modal_book_title"></td>
                            </tr>
                            <tr>
                                <th>Author:</th>
                                <td id="modal_book_author"></td>
                            </tr>
                            <tr>
                                <th>ISBN:</th>
                                <td id="modal_book_isbn"></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td id="modal_book_category"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Issue Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Issue Date:</th>
                                <td id="modal_issue_date"></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td id="modal_due_date"></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td id="modal_return_date"></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="modal_status"></td>
                            </tr>
                            <tr>
                                <th>Fine Amount:</th>
                                <td id="modal_fine_amount"></td>
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

<?php include '../includes/admin_footer.php'; ?>

<script>
function showIssueDetails(issue) {
    document.getElementById('modal_user_name').textContent = issue.full_name;
    document.getElementById('modal_user_reg').textContent = issue.registration_number;
    document.getElementById('modal_user_type').innerHTML = '<span class="badge bg-' + (issue.user_type === 'faculty' ? 'primary' : 'info') + '">' + issue.user_type.charAt(0).toUpperCase() + issue.user_type.slice(1) + '</span>';
    
    document.getElementById('modal_book_title').textContent = issue.title;
    document.getElementById('modal_book_author').textContent = issue.author;
    document.getElementById('modal_book_isbn').textContent = issue.isbn;
    document.getElementById('modal_book_category').textContent = issue.category_name || 'Uncategorized';
    
    document.getElementById('modal_issue_date').textContent = formatDate(issue.issue_date);
    document.getElementById('modal_due_date').textContent = formatDate(issue.due_date);
    document.getElementById('modal_return_date').textContent = issue.return_date ? formatDate(issue.return_date) : 'Not returned yet';
    
    let statusBadge = '';
    switch (issue.status) {
        case 'issued':
            if (issue.days_remaining < 0) {
                statusBadge = '<span class="badge bg-danger">Overdue</span>';
            } else if (issue.days_remaining <= 3) {
                statusBadge = '<span class="badge bg-warning">Due Soon</span>';
            } else {
                statusBadge = '<span class="badge bg-primary">Active</span>';
            }
            break;
        case 'overdue':
            statusBadge = '<span class="badge bg-danger">Overdue</span>';
            break;
        case 'returned':
            statusBadge = '<span class="badge bg-success">Returned</span>';
            break;
        default:
            statusBadge = '<span class="badge bg-secondary">' + issue.status + '</span>';
    }
    document.getElementById('modal_status').innerHTML = statusBadge;
    
    document.getElementById('modal_fine_amount').textContent = issue.fine_amount ? '₹' + parseFloat(issue.fine_amount).toFixed(2) : 'No fine';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}
</script>
