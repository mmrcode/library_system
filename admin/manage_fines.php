<?php
/**
 * Manage Fines - Library Management System
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
    } else {
        if (isset($_POST['mark_paid'])) {
            $fineId = (int)$_POST['fine_id'];
            $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            $result = $db->update('fines', [
                'status' => 'paid',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['fine_id' => $fineId]);
            
            if ($result) {
                $fine = $db->fetchRow("SELECT f.*, u.full_name FROM fines f JOIN book_issues bi ON f.issue_id = bi.issue_id JOIN users u ON bi.user_id = u.user_id WHERE f.fine_id = ?", [$fineId]);
                logActivity(getCurrentUser()['user_id'], 'fine_payment', "Fine payment recorded for " . $fine['full_name'] . " - Amount: ₹" . $fine['fine_amount']);
                setFlashMessage('Fine payment recorded successfully!', 'success');
            } else {
                setFlashMessage('Failed to record payment. Please try again.', 'error');
            }
        } elseif (isset($_POST['waive_fine'])) {
            $fineId = (int)$_POST['fine_id'];
            $reason = sanitizeInput($_POST['waive_reason'] ?? '');
            
            $result = $db->update('fines', [
                'status' => 'waived',
                'waived_date' => date('Y-m-d H:i:s'),
                'waive_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['fine_id' => $fineId]);
            
            if ($result) {
                $fine = $db->fetchRow("SELECT f.*, u.full_name FROM fines f JOIN book_issues bi ON f.issue_id = bi.issue_id JOIN users u ON bi.user_id = u.user_id WHERE f.fine_id = ?", [$fineId]);
                logActivity(getCurrentUser()['user_id'], 'fine_waive', "Fine waived for " . $fine['full_name'] . " - Amount: ₹" . $fine['fine_amount'] . " - Reason: " . $reason);
                setFlashMessage('Fine waived successfully!', 'success');
            } else {
                setFlashMessage('Failed to waive fine. Please try again.', 'error');
            }
        }
    }
}

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
    $whereConditions[] = "f.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.registration_number LIKE ? OR b.title LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$totalFines = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM fines f
    JOIN book_issues bi ON f.issue_id = bi.issue_id
    JOIN users u ON bi.user_id = u.user_id
    JOIN books b ON bi.book_id = b.book_id
    WHERE {$whereClause}
", $params) ?? 0;

$totalPages = ceil($totalFines / $limit);

// Get fines
$fines = $db->fetchAll("
    SELECT f.*, u.full_name, u.registration_number, u.user_type,
           b.title, b.author, bi.issue_date, bi.due_date, bi.return_date,
           DATEDIFF(COALESCE(bi.return_date, CURDATE()), bi.due_date) as days_overdue
    FROM fines f
    JOIN book_issues bi ON f.issue_id = bi.issue_id
    JOIN users u ON bi.user_id = u.user_id
    JOIN books b ON bi.book_id = b.book_id
    WHERE {$whereClause}
    ORDER BY f.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Get summary statistics
$stats = $db->fetchRow("
    SELECT 
        COUNT(*) as total_fines,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_fines,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_fines,
        COUNT(CASE WHEN status = 'waived' THEN 1 END) as waived_fines,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN fine_amount END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN fine_amount END), 0) as collected_amount,
        COALESCE(SUM(fine_amount), 0) as total_amount
    FROM fines
");

$pageTitle = 'Manage Fines';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-money-bill-wave me-2"></i>Manage Fines
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportFines()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="generateFineReport()">
                            <i class="fas fa-chart-bar me-1"></i>Report
                        </button>
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
                            <h3 class="text-primary"><?php echo number_format($stats['total_fines'] ?? 0); ?></h3>
                            <p class="mb-0">Total Fines</p>
                            <small class="text-muted">₹<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card warning">
                        <div class="card-body text-center">
                            <h3 class="text-warning"><?php echo number_format($stats['pending_fines'] ?? 0); ?></h3>
                            <p class="mb-0">Pending</p>
                            <small class="text-muted">₹<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card success">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo number_format($stats['paid_fines'] ?? 0); ?></h3>
                            <p class="mb-0">Paid</p>
                            <small class="text-muted">₹<?php echo number_format($stats['collected_amount'] ?? 0, 2); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card dashboard-card">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?php echo number_format($stats['waived_fines'] ?? 0); ?></h3>
                            <p class="mb-0">Waived</p>
                            <small class="text-muted">Administrative</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="User name, registration, book title">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="waived" <?php echo ($status == 'waived') ? 'selected' : ''; ?>>Waived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
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
                                <a href="manage_fines.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Fines Table -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Fines Management 
                        <?php if ($totalFines > 0): ?>
                            (<?php echo number_format($totalFines); ?> records)
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($fines)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Book</th>
                                        <th>Issue Details</th>
                                        <th>Fine Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fine['full_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($fine['registration_number']); ?>
                                                    <span class="badge bg-<?php echo $fine['user_type'] == 'faculty' ? 'primary' : 'info'; ?> ms-1">
                                                        <?php echo ucfirst($fine['user_type']); ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fine['title']); ?></strong><br>
                                                <small class="text-muted">by <?php echo htmlspecialchars($fine['author']); ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Issue:</strong> <?php echo formatDate($fine['issue_date']); ?><br>
                                                    <strong>Due:</strong> <?php echo formatDate($fine['due_date']); ?><br>
                                                    <?php if ($fine['return_date']): ?>
                                                        <strong>Return:</strong> <?php echo formatDate($fine['return_date']); ?><br>
                                                    <?php endif; ?>
                                                    <span class="text-danger">
                                                        <strong><?php echo $fine['days_overdue']; ?> days overdue</strong>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger fs-5">₹<?php echo number_format($fine['fine_amount'], 2); ?></span>
                                                <br><small class="text-muted">Created: <?php echo formatDate($fine['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = 'secondary';
                                                $statusText = ucfirst($fine['status']);
                                                switch ($fine['status']) {
                                                    case 'pending':
                                                        $badgeClass = 'warning';
                                                        break;
                                                    case 'paid':
                                                        $badgeClass = 'success';
                                                        break;
                                                    case 'waived':
                                                        $badgeClass = 'info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $badgeClass; ?> fs-6">
                                                    <?php echo $statusText; ?>
                                                </span>
                                                <?php if ($fine['status'] == 'paid' && $fine['payment_date']): ?>
                                                    <br><small class="text-muted">
                                                        Paid: <?php echo formatDate($fine['payment_date']); ?>
                                                        <?php if ($fine['payment_method']): ?>
                                                            <br>via <?php echo ucfirst($fine['payment_method']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php elseif ($fine['status'] == 'waived' && $fine['waived_date']): ?>
                                                    <br><small class="text-muted">
                                                        Waived: <?php echo formatDate($fine['waived_date']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#fineDetailsModal"
                                                            onclick="showFineDetails(<?php echo htmlspecialchars(json_encode($fine)); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($fine['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                                data-bs-toggle="modal" data-bs-target="#markPaidModal"
                                                                onclick="preparePayment(<?php echo $fine['fine_id']; ?>, <?php echo $fine['fine_amount']; ?>, '<?php echo htmlspecialchars($fine['full_name']); ?>')">
                                                            <i class="fas fa-money-bill"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                data-bs-toggle="modal" data-bs-target="#waiveFineModal"
                                                                onclick="prepareWaive(<?php echo $fine['fine_id']; ?>, <?php echo $fine['fine_amount']; ?>, '<?php echo htmlspecialchars($fine['full_name']); ?>')">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
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
                            <nav aria-label="Fines pagination" class="mt-4">
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
                            <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No fines found</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || $status !== 'all'): ?>
                                    Try adjusting your search criteria.
                                <?php else: ?>
                                    No fines have been recorded yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Fine Details Modal -->
<div class="modal fade" id="fineDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fine Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Fine as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="fine_id" id="payment_fine_id">
                    
                    <div class="alert alert-info">
                        <strong>User:</strong> <span id="payment_user_name"></span><br>
                        <strong>Amount:</strong> ₹<span id="payment_amount"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="payment_notes" name="notes" rows="3" placeholder="Transaction reference, receipt number, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_paid" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Waive Fine Modal -->
<div class="modal fade" id="waiveFineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Waive Fine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="fine_id" id="waive_fine_id">
                    
                    <div class="alert alert-warning">
                        <strong>User:</strong> <span id="waive_user_name"></span><br>
                        <strong>Amount:</strong> ₹<span id="waive_amount"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="waive_reason" class="form-label">Reason for Waiving <span class="text-danger">*</span></label>
                        <select class="form-select" id="waive_reason" name="waive_reason" required>
                            <option value="">Select reason...</option>
                            <option value="first_time_offender">First time offender</option>
                            <option value="student_hardship">Student financial hardship</option>
                            <option value="library_error">Library processing error</option>
                            <option value="book_unavailable">Book was unavailable for return</option>
                            <option value="system_maintenance">System maintenance issue</option>
                            <option value="administrative_decision">Administrative decision</option>
                            <option value="other">Other (specify in notes)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="waive_notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="waive_notes" name="notes" rows="3" placeholder="Additional details about the waiver decision"></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The fine will be permanently waived.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="waive_fine" class="btn btn-warning">Waive Fine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
function showFineDetails(fine) {
    // Implementation for showing fine details
    console.log('Fine details:', fine);
}

function preparePayment(fineId, amount, userName) {
    document.getElementById('payment_fine_id').value = fineId;
    document.getElementById('payment_amount').textContent = parseFloat(amount).toFixed(2);
    document.getElementById('payment_user_name').textContent = userName;
}

function prepareWaive(fineId, amount, userName) {
    document.getElementById('waive_fine_id').value = fineId;
    document.getElementById('waive_amount').textContent = parseFloat(amount).toFixed(2);
    document.getElementById('waive_user_name').textContent = userName;
}

function exportFines() {
    // Implementation for exporting fines
    alert('Export functionality will be implemented');
}

function generateFineReport() {
    window.location.href = 'reports.php?type=fines';
}
</script>
