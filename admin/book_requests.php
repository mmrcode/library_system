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
$view_request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;

// Get requests
$requests = $requestSystem->getAllBookRequests($status_filter ?: null);

// Get specific request details if viewing
$viewRequest = null;
if ($view_request_id) {
    foreach ($requests as $request) {
        if ($request['request_id'] == $view_request_id) {
            $viewRequest = $request;
            break;
        }
    }
}

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
                                        <tr data-request-id="<?php echo $request['request_id']; ?>"
                                            data-student-name="<?php echo htmlspecialchars($request['full_name']); ?>"
                                            data-reg-number="<?php echo htmlspecialchars($request['registration_number']); ?>"
                                            data-email="<?php echo htmlspecialchars($request['email']); ?>"
                                            data-book-title="<?php echo htmlspecialchars($request['title']); ?>"
                                            data-author="<?php echo htmlspecialchars($request['author']); ?>"
                                            data-isbn="<?php echo htmlspecialchars($request['isbn']); ?>"
                                            data-request-type="<?php echo htmlspecialchars($request['request_type']); ?>"
                                            data-priority="<?php echo htmlspecialchars($request['priority']); ?>"
                                            data-duration="<?php echo htmlspecialchars($request['requested_duration']); ?>"
                                            data-notes="<?php echo htmlspecialchars($request['notes'] ?? ''); ?>"
                                            data-status="<?php echo htmlspecialchars($request['status']); ?>"
                                            data-request-date="<?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?>"
                                            data-admin-notes="<?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?>">
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

<!-- Request Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function processRequest(requestId, status) {
    // Validate inputs
    if (!requestId || !status) {
        alert('Invalid request parameters.');
        return;
    }
    
    document.getElementById('processRequestId').value = requestId;
    document.getElementById('processStatus').value = status;
    
    // Clear previous notes
    document.getElementById('admin_notes').value = '';
    
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
    
    const bulkStatus = document.querySelector('select[name="bulk_status"]').value;
    if (!bulkStatus) {
        alert('Please select an action to perform.');
        return false;
    }
    
    return confirm(`Are you sure you want to ${bulkStatus} ${selected.length} request(s)?`);
}

// Add form submission handler for the process form
document.addEventListener('DOMContentLoaded', function() {
    const processForm = document.getElementById('processForm');
    if (processForm) {
        processForm.addEventListener('submit', function(e) {
            const requestId = document.getElementById('processRequestId').value;
            const status = document.getElementById('processStatus').value;
            
            if (!requestId || !status) {
                e.preventDefault();
                alert('Missing required information. Please try again.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('processSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
            submitBtn.disabled = true;
            
            // Re-enable button after a delay in case of errors
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
});

function viewRequestDetails(requestId) {
    // Find the request data from the page
    const requestRow = document.querySelector(`tr[data-request-id="${requestId}"]`);
    if (!requestRow) {
        alert('Request details not found.');
        return;
    }
    
    // Get request data from data attributes
    const requestData = {
        id: requestRow.dataset.requestId,
        studentName: requestRow.dataset.studentName,
        regNumber: requestRow.dataset.regNumber,
        email: requestRow.dataset.email,
        bookTitle: requestRow.dataset.bookTitle,
        author: requestRow.dataset.author,
        isbn: requestRow.dataset.isbn,
        requestType: requestRow.dataset.requestType,
        priority: requestRow.dataset.priority,
        duration: requestRow.dataset.duration,
        notes: requestRow.dataset.notes || 'No notes provided',
        status: requestRow.dataset.status,
        requestDate: requestRow.dataset.requestDate,
        adminNotes: requestRow.dataset.adminNotes || 'No admin notes'
    };
    
    // Populate modal content
    const modalBody = document.getElementById('viewModalBody');
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-user me-2"></i>Student Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${requestData.studentName}</td></tr>
                    <tr><td><strong>Registration:</strong></td><td>${requestData.regNumber}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${requestData.email}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-book me-2"></i>Book Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Title:</strong></td><td>${requestData.bookTitle}</td></tr>
                    <tr><td><strong>Author:</strong></td><td>${requestData.author}</td></tr>
                    <tr><td><strong>ISBN:</strong></td><td>${requestData.isbn}</td></tr>
                </table>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Request Details</h6>
                <table class="table table-sm">
                    <tr><td><strong>Type:</strong></td><td><span class="badge bg-secondary">${requestData.requestType}</span></td></tr>
                    <tr><td><strong>Priority:</strong></td><td><span class="badge bg-${requestData.priority === 'urgent' ? 'danger' : (requestData.priority === 'high' ? 'warning' : 'info')}">${requestData.priority}</span></td></tr>
                    <tr><td><strong>Duration:</strong></td><td>${requestData.duration} days</td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge bg-${requestData.status === 'pending' ? 'warning' : (requestData.status === 'approved' ? 'info' : (requestData.status === 'fulfilled' ? 'success' : 'danger'))}">${requestData.status}</span></td></tr>
                    <tr><td><strong>Request Date:</strong></td><td>${requestData.requestDate}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-sticky-note me-2"></i>Notes</h6>
                <div class="mb-3">
                    <strong>Student Notes:</strong><br>
                    <div class="border p-2 bg-light rounded">${requestData.notes}</div>
                </div>
                <div>
                    <strong>Admin Notes:</strong><br>
                    <div class="border p-2 bg-light rounded">${requestData.adminNotes}</div>
                </div>
            </div>
        </div>
    `;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>
