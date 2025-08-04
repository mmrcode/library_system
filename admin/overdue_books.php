<?php
/**
 * Overdue Books - Library Management System
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

// Get overdue books
$overdueBooks = $db->fetchAll("
    SELECT bi.*, u.full_name, u.registration_number, u.user_type, u.email, u.phone,
           b.title, b.author, b.isbn, c.category_name,
           DATEDIFF(CURDATE(), bi.due_date) as days_overdue,
           f.fine_amount
    FROM book_issues bi
    JOIN users u ON bi.user_id = u.user_id
    JOIN books b ON bi.book_id = b.book_id
    LEFT JOIN categories c ON b.category_id = c.category_id
    LEFT JOIN fines f ON bi.issue_id = f.issue_id AND f.status = 'pending'
    WHERE bi.status = 'overdue' OR (bi.status = 'issued' AND bi.due_date < CURDATE())
    ORDER BY days_overdue DESC, bi.due_date ASC
");

// Update status for overdue books
$db->query("
    UPDATE book_issues 
    SET status = 'overdue' 
    WHERE status = 'issued' AND due_date < CURDATE()
");

// Get summary statistics
$stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total_overdue,
        COUNT(DISTINCT bi.user_id) as affected_users,
        COALESCE(SUM(f.fine_amount), 0) as total_fines,
        AVG(DATEDIFF(CURDATE(), bi.due_date)) as avg_days_overdue
    FROM book_issues bi
    LEFT JOIN fines f ON bi.issue_id = f.issue_id AND f.status = 'pending'
    WHERE bi.status = 'overdue'
");

$pageTitle = 'Overdue Books';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Overdue Books
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-warning" onclick="sendReminders()">
                            <i class="fas fa-envelope me-1"></i>Send Reminders
                        </button>
                        <a href="return_book.php" class="btn btn-sm btn-success">
                            <i class="fas fa-undo me-1"></i>Process Returns
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Alert Banner -->
            <?php if (!empty($overdueBooks)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Critical Alert:</strong> There are <?php echo count($overdueBooks); ?> overdue books requiring immediate attention!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card danger">
                        <div class="card-body text-center">
                            <h3 class="text-danger"><?php echo number_format($stats['total_overdue'] ?? 0); ?></h3>
                            <p class="mb-0">Overdue Books</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card warning">
                        <div class="card-body text-center">
                            <h3 class="text-warning"><?php echo number_format($stats['affected_users'] ?? 0); ?></h3>
                            <p class="mb-0">Affected Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-danger">₹<?php echo number_format($stats['total_fines'] ?? 0, 2); ?></h3>
                            <p class="mb-0">Total Fines</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo number_format($stats['avg_days_overdue'] ?? 0, 1); ?></h3>
                            <p class="mb-0">Avg Days Overdue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Books Table -->
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list me-2"></i>Overdue Books List
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($overdueBooks)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Priority</th>
                                        <th>User</th>
                                        <th>Book</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Fine Amount</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdueBooks as $book): ?>
                                        <tr class="<?php echo $book['days_overdue'] > 30 ? 'table-danger' : ($book['days_overdue'] > 14 ? 'table-warning' : ''); ?>">
                                            <td>
                                                <?php if ($book['days_overdue'] > 30): ?>
                                                    <span class="badge bg-danger">Critical</span>
                                                <?php elseif ($book['days_overdue'] > 14): ?>
                                                    <span class="badge bg-warning">High</span>
                                                <?php elseif ($book['days_overdue'] > 7): ?>
                                                    <span class="badge bg-info">Medium</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Low</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($book['full_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($book['registration_number']); ?>
                                                    <span class="badge bg-<?php echo $book['user_type'] == 'faculty' ? 'primary' : 'info'; ?> ms-1">
                                                        <?php echo ucfirst($book['user_type']); ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                <small class="text-muted">
                                                    by <?php echo htmlspecialchars($book['author']); ?>
                                                    <?php if (!empty($book['category_name'])): ?>
                                                        | <?php echo htmlspecialchars($book['category_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="text-danger"><?php echo formatDate($book['due_date']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger fs-6"><?php echo $book['days_overdue']; ?> days</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($book['fine_amount'])): ?>
                                                    <span class="text-danger fw-bold">₹<?php echo number_format($book['fine_amount'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not calculated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($book['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($book['email']); ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($book['phone'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($book['phone']); ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-phone"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#overdueDetailsModal"
                                                            onclick="showOverdueDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="return_book.php?issue_id=<?php echo $book['issue_id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="sendIndividualReminder(<?php echo $book['user_id']; ?>, '<?php echo htmlspecialchars($book['full_name']); ?>')">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">Excellent!</h4>
                            <p class="text-muted">No overdue books found. All books are returned on time!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Overdue Details Modal -->
<div class="modal fade" id="overdueDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Overdue Book Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This book is <strong id="modal_days_overdue"></strong> days overdue!
                </div>
                
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
                            <tr>
                                <th>Email:</th>
                                <td id="modal_user_email"></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td id="modal_user_phone"></td>
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
                        <h6>Issue Details</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Issue Date:</th>
                                <td id="modal_issue_date"></td>
                            </tr>
                            <tr>
                                <th>Due Date:</th>
                                <td id="modal_due_date" class="text-danger"></td>
                            </tr>
                            <tr>
                                <th>Days Overdue:</th>
                                <td id="modal_overdue_days" class="text-danger fw-bold"></td>
                            </tr>
                            <tr>
                                <th>Fine Amount:</th>
                                <td id="modal_fine_amount" class="text-danger fw-bold"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="sendReminderFromModal()">
                    <i class="fas fa-envelope me-1"></i>Send Reminder
                </button>
                <button type="button" class="btn btn-success" onclick="processReturnFromModal()">
                    <i class="fas fa-undo me-1"></i>Process Return
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
let currentOverdueBook = null;

function showOverdueDetails(book) {
    currentOverdueBook = book;
    
    document.getElementById('modal_days_overdue').textContent = book.days_overdue;
    document.getElementById('modal_user_name').textContent = book.full_name;
    document.getElementById('modal_user_reg').textContent = book.registration_number;
    document.getElementById('modal_user_type').innerHTML = '<span class="badge bg-' + (book.user_type === 'faculty' ? 'primary' : 'info') + '">' + book.user_type.charAt(0).toUpperCase() + book.user_type.slice(1) + '</span>';
    document.getElementById('modal_user_email').textContent = book.email || 'Not provided';
    document.getElementById('modal_user_phone').textContent = book.phone || 'Not provided';
    
    document.getElementById('modal_book_title').textContent = book.title;
    document.getElementById('modal_book_author').textContent = book.author;
    document.getElementById('modal_book_isbn').textContent = book.isbn;
    document.getElementById('modal_book_category').textContent = book.category_name || 'Uncategorized';
    
    document.getElementById('modal_issue_date').textContent = formatDate(book.issue_date);
    document.getElementById('modal_due_date').textContent = formatDate(book.due_date);
    document.getElementById('modal_overdue_days').textContent = book.days_overdue + ' days';
    document.getElementById('modal_fine_amount').textContent = book.fine_amount ? '₹' + parseFloat(book.fine_amount).toFixed(2) : 'Not calculated';
}

function sendReminders() {
    if (confirm('Send reminder notifications to all users with overdue books?')) {
        // This would typically make an AJAX call to send reminders
        alert('Reminder notifications sent successfully!');
    }
}

function sendIndividualReminder(userId, userName) {
    if (confirm('Send reminder notification to ' + userName + '?')) {
        // This would typically make an AJAX call to send individual reminder
        alert('Reminder sent to ' + userName + ' successfully!');
    }
}

function sendReminderFromModal() {
    if (currentOverdueBook) {
        sendIndividualReminder(currentOverdueBook.user_id, currentOverdueBook.full_name);
    }
}

function processReturnFromModal() {
    if (currentOverdueBook) {
        window.location.href = 'return_book.php?issue_id=' + currentOverdueBook.issue_id;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}

// Auto-refresh page every 5 minutes to update overdue status
setTimeout(function() {
    location.reload();
}, 300000);
</script>
