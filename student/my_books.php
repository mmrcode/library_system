<?php
/**
 * Student My Books - Library Management System
 *
 * idea: show student's currently issued/overdue/returned books with quick
 * status badges and a small details modal. tried to keep UI simple so it's
 * easy to navigate on mobile too. comments added for viva prep 😅
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

// Guard: only students can view their own books
requireStudent();

$db = Database::getInstance();
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Get filter parameters (status tab + pagination)
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on status filter (we append extra conditions depending on tab)
$whereConditions = ["bi.user_id = ?"];
$params = [$userId];

switch ($status) {
    case 'issued':
        $whereConditions[] = "bi.status = 'issued'";
        break;
    case 'overdue':
        $whereConditions[] = "bi.status = 'overdue'";
        break;
    case 'returned':
        $whereConditions[] = "bi.status = 'returned'";
    // 'all' shows all statuses
}

// Now we have our WHERE conditions, let's build the final query string
$whereClause = implode(' AND ', $whereConditions); // final WHERE for both count and list queries

// Get total count (for pagination and tab labels)
// We need to know how many books we have in total to calculate pagination
$totalBooks = $db->fetchColumn("\n    SELECT COUNT(*) \n    FROM book_issues bi \n    WHERE {$whereClause}\n", $params) ?? 0;

// Calculate total pages based on limit and total books
$totalPages = ceil($totalBooks / $limit);

// Get books (joins title/category + pending fine if any; orders by issue date)
// This is the main query that fetches all the book data we need
$books = $db->fetchAll("\n    SELECT bi.*, b.title, b.author, b.isbn, c.category_name,\n           DATEDIFF(bi.due_date, CURDATE()) as days_remaining,\n           CASE \n               WHEN bi.status = 'returned' THEN 'Returned'\n               WHEN bi.status = 'overdue' THEN 'Overdue'\n               WHEN DATEDIFF(bi.due_date, CURDATE()) < 0 THEN 'Overdue'\n               WHEN DATEDIFF(bi.due_date, CURDATE()) <= 3 THEN 'Due Soon'\n               ELSE 'Active'\n           END as display_status,\n           f.fine_amount\n    FROM book_issues bi\n    JOIN books b ON bi.book_id = b.book_id\n    LEFT JOIN categories c ON b.category_id = c.category_id\n    LEFT JOIN fines f ON bi.issue_id = f.issue_id AND f.status = 'pending'\n    WHERE {$whereClause}\n    ORDER BY bi.issue_date DESC\n    LIMIT {$limit} OFFSET {$offset}\n", $params);

// Get summary statistics (for the 4 cards on top)
// We need some quick stats to display on the dashboard
$stats = $db->fetchRow("\n    SELECT \n        COUNT(CASE WHEN status = 'issued' THEN 1 END) as active_count,\n        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,\n        COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_count,\n        COUNT(*) as total_count\n    FROM book_issues \n    WHERE user_id = ?\n", [$userId]);

$pageTitle = 'My Books';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-12 ms-sm-auto px-md-4">
            <div class="card mt-3">
                <div class="card-body">
                    <?php if (!empty($books)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Issued On</th>
                                        <th>Due On</th>
                                        <th>Status</th>
                                        <th>Fine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo generateBookThumbnailFromData($book, 'small'); ?>" 
                                                         class="me-3 rounded" 
                                                         style="width: 50px; height: 60px; object-fit: cover;" 
                                                         alt="Book Cover">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($book['author']); ?><br>
                                                            ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatDate($book['issue_date']); ?></td>
                                            <td>
                                                <?php echo formatDate($book['due_date']); ?>
                                                <?php if ($book['status'] !== 'returned' && $book['days_remaining'] < 0): ?>
                                                    <br><small class="text-danger">
                                                        <?php echo abs($book['days_remaining']); ?> days overdue
                                                    </small>
                                                <?php elseif ($book['status'] !== 'returned' && $book['days_remaining'] <= 3): ?>
                                                    <br><small class="text-warning">
                                                        <?php echo $book['days_remaining']; ?> days remaining
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Set the badge class based on the book's status
                                                $badgeClass = 'secondary';
                                                switch ($book['display_status']) {
                                                    case 'Active':
                                                        $badgeClass = 'success';
                                                        break;
                                                    case 'Due Soon':
                                                        $badgeClass = 'warning';
                                                        break;
                                                    case 'Overdue':
                                                        $badgeClass = 'danger';
                                                        break;
                                                    case 'Returned':
                                                        $badgeClass = 'info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo $book['display_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($book['fine_amount'])): ?>
                                                    <span class="text-danger">₹<?php echo number_format($book['fine_amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#bookDetailsModal"
                                                        onclick="showBookDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($book['status'] !== 'returned'): ?>
                                                    <span class="text-muted ms-2">
                                                        <i class="fas fa-info-circle" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Contact librarian to return this book"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination (centered) -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Books pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>">
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
                                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Books (empty states per tab) -->
                        <div class="text-center py-5">
                            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">
                                <?php
                                switch ($status) {
                                    case 'issued':
                                        echo 'No active book issues';
                                        break;
                                    case 'overdue':
                                        echo 'No overdue books';
                                        break;
                                    case 'returned':
                                        echo 'No returned books';
                                        break;
                                    default:
                                        echo 'No books found';
                                }
                                ?>
                            </h4>
                            <p class="text-muted">
                                <?php if ($status === 'all' || $status === 'issued'): ?>
                                    <a href="search_books.php">Search for books</a> to start your reading journey!
                                <?php else: ?>
                                    <?php if ($status === 'overdue'): ?>
                                        Great! You have no overdue books.
                                    <?php else: ?>
                                        You haven't returned any books yet.
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Book Details Modal (per-issue detail view) -->
<div class="modal fade" id="bookDetailsModal" tabindex="-1" aria-labelledby="bookDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookDetailsModalLabel">Book Issue Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div id="modalBookNameDisplay" class="book-name-display d-flex align-items-center justify-content-center text-center">
                            <h3 id="modalBookNameTitle" class="book-title-display"></h3>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 id="modalBookTitle"></h4>
                        <p class="text-muted mb-3" id="modalBookAuthor"></p>
                        
                        <table class="table table-sm">
                            <tr>
                                <th>ISBN:</th>
                                <td id="modalBookISBN"></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td id="modalBookCategory"></td>
                            </tr>
                            <tr>
                                <th>Issue Date:</th>
                                <td id="modalIssueDate"></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td id="modalDueDate"></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td id="modalReturnDate"></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="modalStatus"></td>
                            </tr>
                            <tr>
                                <th>Fine Amount:</th>
                                <td id="modalFineAmount"></td>
                            </tr>
                        </table>
                        
                        <div id="modalNotes" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div id="modalActions"></div>
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
// show selected issue in a modal (format a few fields nicely)
function showBookDetails(book) {
    // Set book name in the display area
    document.getElementById('modalBookNameTitle').textContent = book.title;
    
    // Set book details
    document.getElementById('modalBookTitle').textContent = book.title;
    document.getElementById('modalBookAuthor').textContent = 'by ' + book.author;
    document.getElementById('modalBookISBN').textContent = book.isbn;
    document.getElementById('modalBookCategory').textContent = book.category_name || 'Uncategorized';
    document.getElementById('modalIssueDate').textContent = formatDate(book.issue_date);
    document.getElementById('modalDueDate').textContent = formatDate(book.due_date);
    
    // Return date
    const returnDateElement = document.getElementById('modalReturnDate');
    if (book.return_date) {
        returnDateElement.textContent = formatDate(book.return_date);
    } else {
        returnDateElement.textContent = 'Not returned yet';
    }
    
    // Status (convert to colored badge)
    const statusElement = document.getElementById('modalStatus');
    let statusBadge = '';
    switch (book.display_status) {
        case 'Active':
            statusBadge = '<span class="badge bg-success">Active</span>';
            break;
        case 'Due Soon':
            statusBadge = '<span class="badge bg-warning">Due Soon</span>';
            break;
        case 'Overdue':
            statusBadge = '<span class="badge bg-danger">Overdue</span>';
            break;
        case 'Returned':
            statusBadge = '<span class="badge bg-info">Returned</span>';
            break;
        default:
            statusBadge = '<span class="badge bg-secondary">' + book.display_status + '</span>';
    }
    statusElement.innerHTML = statusBadge;
    
    // Fine amount (₹ shown only if pending fine exists)
    const fineElement = document.getElementById('modalFineAmount');
    if (book.fine_amount && book.fine_amount > 0) {
        fineElement.innerHTML = '<span class="text-danger">₹' + parseFloat(book.fine_amount).toFixed(2) + '</span>';
    } else {
        fineElement.textContent = 'No fine';
    }
    
    // Notes (small helpful warnings for student)
    const notesElement = document.getElementById('modalNotes');
    if (book.status !== 'returned' && book.days_remaining < 0) {
        notesElement.innerHTML = '<div class="alert alert-danger"><strong>Note:</strong> This book is ' + Math.abs(book.days_remaining) + ' days overdue. Please return it as soon as possible to avoid additional fines.</div>';
    } else if (book.status !== 'returned' && book.days_remaining <= 3) {
        notesElement.innerHTML = '<div class="alert alert-warning"><strong>Reminder:</strong> This book is due in ' + book.days_remaining + ' days. Please plan to return it on time.</div>';
    } else {
        notesElement.innerHTML = '';
    }
    
    // Actions (for now informational – return action handled by librarian)
    const actionsElement = document.getElementById('modalActions');
    if (book.status !== 'returned') {
        actionsElement.innerHTML = '<span class="text-info"><i class="fas fa-info-circle me-1"></i>Contact the librarian to return this book</span>';
    } else {
        actionsElement.innerHTML = '';
    }
}



// Initialize tooltips (for the info icons)
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
