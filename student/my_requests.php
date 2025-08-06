<?php
/**
 * My Book Requests - Library Management System
 * Student request tracking dashboard
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
require_once '../includes/request_functions.php';

// Require student access
requireStudent();

$currentUser = getCurrentUser();

// Handle request cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
    $request_id = (int)$_POST['request_id'];
    
    // Verify the request belongs to the current user
    $request = $requestSystem->getBookRequest($request_id);
    if ($request && $request['user_id'] == $currentUser['user_id'] && $request['status'] === 'pending') {
        $result = $requestSystem->processBookRequest($request_id, 'cancelled', $currentUser['user_id'], 'Cancelled by student');
        $_SESSION['flash_message'] = $result['success'] ? 'Request cancelled successfully.' : 'Failed to cancel request.';
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
    }
    
    header('Location: my_requests.php');
    exit;
}

// Get user's book requests
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$requests = $requestSystem->getUserBookRequests($currentUser['user_id'], $status_filter ?: null);

// Get request statistics
$stats = [
    'total' => count($requestSystem->getUserBookRequests($currentUser['user_id'])),
    'pending' => count($requestSystem->getUserBookRequests($currentUser['user_id'], 'pending')),
    'approved' => count($requestSystem->getUserBookRequests($currentUser['user_id'], 'approved')),
    'rejected' => count($requestSystem->getUserBookRequests($currentUser['user_id'], 'rejected')),
    'fulfilled' => count($requestSystem->getUserBookRequests($currentUser['user_id'], 'fulfilled'))
];

$pageTitle = 'My Book Requests';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-list-alt me-2"></i>My Book Requests
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="search_books.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Request New Book
                    </a>
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
                            <h5 class="card-title text-primary"><?php echo $stats['total']; ?></h5>
                            <p class="card-text small">Total Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['pending']; ?></h5>
                            <p class="card-text small">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['approved']; ?></h5>
                            <p class="card-text small">Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo $stats['rejected']; ?></h5>
                            <p class="card-text small">Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['fulfilled']; ?></h5>
                            <p class="card-text small">Fulfilled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === '' ? 'active' : ''; ?>" href="my_requests.php">
                        All Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="my_requests.php?status=pending">
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="my_requests.php?status=approved">
                        Approved (<?php echo $stats['approved']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="my_requests.php?status=rejected">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'fulfilled' ? 'active' : ''; ?>" href="my_requests.php?status=fulfilled">
                        Fulfilled (<?php echo $stats['fulfilled']; ?>)
                    </a>
                </li>
            </ul>

            <!-- Requests List -->
            <?php if (!empty($requests)): ?>
                <div class="row">
                    <?php foreach ($requests as $request): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php 
                                        echo $request['status'] === 'pending' ? 'warning' : 
                                            ($request['status'] === 'approved' ? 'info' : 
                                            ($request['status'] === 'fulfilled' ? 'success' : 
                                            ($request['status'] === 'rejected' ? 'danger' : 'secondary'))); 
                                    ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($request['request_date'])); ?>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($request['title']); ?></h6>
                                    <p class="card-text">
                                        <strong>Author:</strong> <?php echo htmlspecialchars($request['author']); ?><br>
                                        <strong>Type:</strong> <?php echo ucfirst($request['request_type']); ?><br>
                                        <strong>Priority:</strong> <?php echo ucfirst($request['priority']); ?><br>
                                        <strong>Duration:</strong> <?php echo $request['requested_duration']; ?> days
                                    </p>
                                    
                                    <?php if (!empty($request['notes'])): ?>
                                        <div class="alert alert-light py-2">
                                            <small><strong>Your Notes:</strong> <?php echo htmlspecialchars($request['notes']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['admin_notes'])): ?>
                                        <div class="alert alert-info py-2">
                                            <small><strong>Librarian Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
                                            <input type="hidden" name="action" value="cancel_request">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times me-1"></i>Cancel Request
                                            </button>
                                        </form>
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                        <div class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Visit the library to collect your book
                                        </div>
                                    <?php elseif ($request['status'] === 'fulfilled'): ?>
                                        <div class="text-success">
                                            <i class="fas fa-book me-1"></i>
                                            Book issued successfully
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['processed_date']): ?>
                                        <small class="text-muted d-block mt-1">
                                            Processed: <?php echo date('M j, Y g:i A', strtotime($request['processed_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Requests -->
                <div class="text-center py-5">
                    <i class="fas fa-list-alt fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No book requests found</h4>
                    <p class="text-muted">
                        <?php if ($status_filter): ?>
                            No <?php echo $status_filter; ?> requests found.
                        <?php else: ?>
                            You haven't made any book requests yet.
                        <?php endif; ?>
                    </p>
                    <a href="search_books.php" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Browse Books
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/student_footer.php'; ?>
