<?php
/**
 * Admin Book Requests Management - Library Management System
 * Admin dashboard for managing book requests
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
require_once '../includes/request_functions.php';

// Require admin access
requireAdmin();

$currentUser = getCurrentUser();

// Handle request processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'process_request':
                $request_id = (int)$_POST['request_id'];
                $status = $_POST['status'];
                $admin_notes = trim($_POST['admin_notes'] ?? '');
                
                if (in_array($status, ['approved', 'rejected', 'fulfilled'])) {
                    $result = $requestSystem->processBookRequest($request_id, $status, $currentUser['user_id'], $admin_notes);
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'bulk_process':
                $request_ids = $_POST['request_ids'] ?? [];
                $bulk_status = $_POST['bulk_status'];
                $bulk_notes = trim($_POST['bulk_notes'] ?? '');
                
                $processed = 0;
                foreach ($request_ids as $request_id) {
                    $result = $requestSystem->processBookRequest((int)$request_id, $bulk_status, $currentUser['user_id'], $bulk_notes);
                    if ($result['success']) $processed++;
                }
                
                $_SESSION['flash_message'] = "Processed {$processed} request(s) successfully.";
                $_SESSION['flash_type'] = 'success';
                break;
        }
    }
    
    header('Location: book_requests.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get requests
$requests = $requestSystem->getAllBookRequests($status_filter ?: null);

// Filter by search if provided
if ($search) {
    $requests = array_filter($requests, function($request) use ($search) {
        return stripos($request['title'], $search) !== false ||
               stripos($request['author'], $search) !== false ||
               stripos($request['full_name'], $search) !== false ||
               stripos($request['registration_number'], $search) !== false;
    });
}

// Get statistics
$stats = $requestSystem->getRequestStatistics();

$pageTitle = 'Book Requests Management';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-list-alt me-2"></i>Book Requests Management
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="book_requests.php">All Requests</a></li>
                            <li><a class="dropdown-item" href="book_requests.php?status=pending">Pending</a></li>
                            <li><a class="dropdown-item" href="book_requests.php?status=approved">Approved</a></li>
                            <li><a class="dropdown-item" href="book_requests.php?status=rejected">Rejected</a></li>
                            <li><a class="dropdown-item" href="book_requests.php?status=fulfilled">Fulfilled</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total_requests']; ?></h5>
                            <p class="card-text small">Total Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['pending_requests']; ?></h5>
                            <p class="card-text small">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['approved_requests']; ?></h5>
                            <p class="card-text small">Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo $stats['rejected_requests']; ?></h5>
                            <p class="card-text small">Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted"><?php echo $stats['today_requests']; ?></h5>
                            <p class="card-text small">Today</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Bulk Actions -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <?php if ($status_filter): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input type="text" class="form-control me-2" name="search" placeholder="Search by book title, author, or student..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-primary" onclick="toggleBulkActions()">
                        <i class="fas fa-tasks me-1"></i>Bulk Actions
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Panel (Hidden by default) -->
            <div id="bulkActionsPanel" class="card mb-3" style="display: none;">
                <div class="card-body">
                    <form method="POST" id="bulkForm">
                        <input type="hidden" name="action" value="bulk_process">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Action</label>
                                <select name="bulk_status" class="form-select" required>
                                    <option value="">Select action...</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                    <option value="fulfilled">Mark as Fulfilled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes (Optional)</label>
                                <input type="text" name="bulk_notes" class="form-control" placeholder="Add notes for all selected requests...">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary" onclick="return confirmBulkAction()">
                                    <i class="fas fa-check me-1"></i>Process Selected
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Requests Table -->
            <?php if (!empty($requests)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                        </th>
                                        <th>Student</th>
                                        <th>Book</th>
                                        <th>Request Details</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="request_ids[]" value="<?php echo $request['request_id']; ?>" class="request-checkbox">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['registration_number']); ?></small><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                                                <small class="text-muted">by <?php echo htmlspecialchars($request['author']); ?></small><br>
                                                <small class="text-muted">ISBN: <?php echo htmlspecialchars($request['isbn']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo ucfirst($request['request_type']); ?></span>
                                                <span class="badge bg-<?php echo $request['priority'] === 'urgent' ? 'danger' : ($request['priority'] === 'high' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($request['priority']); ?>
                                                </span><br>
                                                <small class="text-muted"><?php echo $request['requested_duration']; ?> days</small>
                                                <?php if (!empty($request['notes'])): ?>
                                                    <br><small class="text-muted">Note: <?php echo htmlspecialchars(substr($request['notes'], 0, 50)) . (strlen($request['notes']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $request['status'] === 'pending' ? 'warning' : 
                                                        ($request['status'] === 'approved' ? 'info' : 
                                                        ($request['status'] === 'fulfilled' ? 'success' : 
                                                        ($request['status'] === 'rejected' ? 'danger' : 'secondary'))); 
                                                ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($request['request_date'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($request['request_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-success" onclick="processRequest(<?php echo $request['request_id']; ?>, 'approved')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger" onclick="processRequest(<?php echo $request['request_id']; ?>, 'rejected')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php elseif ($request['status'] === 'approved'): ?>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="processRequest(<?php echo $request['request_id']; ?>, 'fulfilled')">
                                                        <i class="fas fa-book me-1"></i>Mark Fulfilled
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewRequestDetails(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Requests -->
                <div class="text-center py-5">
                    <i class="fas fa-list-alt fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No book requests found</h4>
                    <p class="text-muted">
                        <?php if ($status_filter): ?>
                            No <?php echo $status_filter; ?> requests found.
                        <?php elseif ($search): ?>
                            No requests found matching "<?php echo htmlspecialchars($search); ?>".
                        <?php else: ?>
                            No book requests have been submitted yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Request Processing Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processModalLabel">Process Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="processForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_request">
                    <input type="hidden" name="request_id" id="processRequestId">
                    <input type="hidden" name="status" id="processStatus">
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="admin_notes" id="admin_notes" rows="3" placeholder="Add any notes for the student..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The student will receive an email notification about this decision.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="processSubmitBtn">
                        <i class="fas fa-check me-1"></i>Process Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processRequest(requestId, status) {
    document.getElementById('processRequestId').value = requestId;
    document.getElementById('processStatus').value = status;
    
    const modal = new bootstrap.Modal(document.getElementById('processModal'));
    const title = document.getElementById('processModalLabel');
    const submitBtn = document.getElementById('processSubmitBtn');
    
    if (status === 'approved') {
        title.textContent = 'Approve Request';
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i>Approve Request';
        submitBtn.className = 'btn btn-success';
    } else if (status === 'rejected') {
        title.textContent = 'Reject Request';
        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i>Reject Request';
        submitBtn.className = 'btn btn-danger';
    } else if (status === 'fulfilled') {
        title.textContent = 'Mark as Fulfilled';
        submitBtn.innerHTML = '<i class="fas fa-book me-1"></i>Mark as Fulfilled';
        submitBtn.className = 'btn btn-info';
    }
    
    modal.show();
}

function toggleBulkActions() {
    const panel = document.getElementById('bulkActionsPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.request-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function confirmBulkAction() {
    const selected = document.querySelectorAll('.request-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select at least one request to process.');
        return false;
    }
    
    return confirm(`Are you sure you want to process ${selected.length} request(s)?`);
}

function viewRequestDetails(requestId) {
    // This could open a detailed view modal
    window.location.href = 'book_requests.php?request_id=' + requestId;
}
</script>

<?php include '../includes/admin_footer.php'; ?>
