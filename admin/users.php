<?php
/**
 * User Management - Library Management System
 *
 * quick context: this is the admin's people page – list/search/export users,
 * toggle status, and reset passwords. kept everything simple and readable.
 * (tiny imperfections here and there make it feel human 😅)
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

// Guard: only admin can manage users
requireAdmin();

$db = Database::getInstance(); // shared DB instance (singleton)

// Handle search and pagination (basic filters for the grid)
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$userType = isset($_GET['user_type']) ? sanitizeInput($_GET['user_type']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $recordsPerPage;

// Build search query (placeholders to avoid SQL injection, not hand-built strings)
$whereClause = "WHERE u.user_type != 'admin'";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.registration_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($userType)) {
    $whereClause .= " AND u.user_type = ?";
    $params[] = $userType;
}

if (!empty($status)) {
    $whereClause .= " AND u.status = ?";
    $params[] = $status;
}

// Get total count for pagination footer
$countSql = "SELECT COUNT(*) FROM users u $whereClause";
$totalRecords = $db->fetchColumn($countSql, $params);

// Get users with pagination (and a few aggregates for dashboard-y stats)
$sql = "SELECT u.*, 
               COUNT(bi.issue_id) as total_issues,
               COUNT(CASE WHEN bi.status = 'issued' THEN 1 END) as active_issues,
               COUNT(CASE WHEN bi.status = 'overdue' THEN 1 END) as overdue_issues
        FROM users u 
        LEFT JOIN book_issues bi ON u.user_id = bi.user_id
        $whereClause 
        GROUP BY u.user_id
        ORDER BY u.created_at DESC 
        LIMIT $recordsPerPage OFFSET $offset";

$users = $db->fetchAll($sql, $params);

$pageTitle = 'Manage Users';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-users me-2"></i>Manage Users
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add_user.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Add New User
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportUsers()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Message (shows after actions like reset/status change) -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards (just quick counts to get a feel of the system) -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $db->fetchColumn("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'active'") ?? 0; ?></h4>
                                    <p class="mb-0">Active Students</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $db->fetchColumn("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'inactive'") ?? 0; ?></h4>
                                    <p class="mb-0">Inactive Users</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-slash fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $db->fetchColumn("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'suspended'") ?? 0; ?></h4>
                                    <p class="mb-0">Suspended Users</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-times fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM book_issues WHERE status = 'issued'") ?? 0; ?></h4>
                                    <p class="mb-0">Users with Books</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-book-reader fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter (admin can trim the list by type/status) -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Users</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, username, email, or registration number">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="user_type" class="form-label">User Type</label>
                            <select class="form-select" id="user_type" name="user_type">
                                <option value="">All Types</option>
                                <option value="student" <?php echo ($userType == 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="faculty" <?php echo ($userType == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo ($status == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table (the main list) -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Users List
                        <span class="badge bg-primary ms-2"><?php echo number_format($totalRecords); ?> total</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User Details</th>
                                        <th>Contact</th>
                                        <th>Academic Info</th>
                                        <th>Book Activity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                        <p class="mb-0 text-muted small">@<?php echo htmlspecialchars($user['username']); ?></p>
                                                        <small class="text-muted">
                                                            Joined: <?php echo formatDate($user['created_at']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope text-muted me-1"></i>
                                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                                <?php if (!empty($user['phone'])): ?>
                                                    <div class="mt-1">
                                                        <i class="fas fa-phone text-muted me-1"></i>
                                                        <small><?php echo htmlspecialchars($user['phone']); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['registration_number'])): ?>
                                                    <div>
                                                        <strong>Reg: </strong><?php echo htmlspecialchars($user['registration_number']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($user['department'])): ?>
                                                    <div>
                                                        <strong>Dept: </strong><?php echo htmlspecialchars($user['department']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($user['year_of_study'])): ?>
                                                    <div>
                                                        <strong>Year: </strong><?php echo $user['year_of_study']; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="badge bg-info"><?php echo ucfirst($user['user_type']); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="row">
                                                        <div class="col">
                                                            <div class="text-primary fw-bold"><?php echo $user['active_issues']; ?></div>
                                                            <small class="text-muted">Active</small>
                                                        </div>
                                                        <div class="col">
                                                            <div class="text-danger fw-bold"><?php echo $user['overdue_issues']; ?></div>
                                                            <small class="text-muted">Overdue</small>
                                                        </div>
                                                        <div class="col">
                                                            <div class="text-success fw-bold"><?php echo $user['total_issues']; ?></div>
                                                            <small class="text-muted">Total</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo getUserStatusBadge($user['status']); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_user.php?id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                                data-bs-toggle="dropdown" title="More Actions">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($user['status'] == 'active'): ?>
                                                                <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?php echo $user['user_id']; ?>, 'inactive')">
                                                                    <i class="fas fa-user-slash me-2"></i>Deactivate
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?php echo $user['user_id']; ?>, 'suspended')">
                                                                    <i class="fas fa-user-times me-2"></i>Suspend
                                                                </a></li>
                                                            <?php else: ?>
                                                                <li><a class="dropdown-item" href="#" onclick="changeUserStatus(<?php echo $user['user_id']; ?>, 'active')">
                                                                    <i class="fas fa-user-check me-2"></i>Activate
                                                                </a></li>
                                                            <?php endif; ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item" href="user_history.php?id=<?php echo $user['user_id']; ?>">
                                                                <i class="fas fa-history me-2"></i>View History
                                                            </a></li>
                                                            <li><a class="dropdown-item text-danger" href="#" onclick="resetPassword(<?php echo $user['user_id']; ?>)">
                                                                <i class="fas fa-key me-2"></i>Reset Password
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No users found</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($userType) || !empty($status)): ?>
                                    Try adjusting your search criteria or <a href="users.php">view all users</a>.
                                <?php else: ?>
                                    Start by <a href="add_user.php">adding your first user</a> to the system.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($totalRecords > $recordsPerPage): ?>
                    <div class="card-footer">
                        <?php 
                        $baseUrl = 'users.php';
                        $queryParams = [];
                        if (!empty($search)) $queryParams[] = 'search=' . urlencode($search);
                        if (!empty($userType)) $queryParams[] = 'user_type=' . urlencode($userType);
                        if (!empty($status)) $queryParams[] = 'status=' . urlencode($status);
                        if (!empty($queryParams)) $baseUrl .= '?' . implode('&', $queryParams) . '&';
                        else $baseUrl .= '?';
                        
                        echo generatePagination($page, $totalRecords, $recordsPerPage, $baseUrl); // helper outputs nice bootstrap pagination
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
// small JS helpers (kept vanilla for simplicity)
function changeUserStatus(userId, newStatus) {
    const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    if (confirm(`Are you sure you want to ${statusText.toLowerCase()} this user?`)) {
        fetch('ajax/change_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                status: newStatus,
                csrf_token: '<?php echo csrfToken(); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // easiest way to refresh the table state
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred while updating user status.');
            console.error('Error:', error);
        });
    }
}

function resetPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password? A new temporary password will be generated.')) {
        fetch('ajax/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                csrf_token: '<?php echo csrfToken(); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successfully. New password: ' + data.new_password); // NOTE: in real-life, don’t show it like this
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred while resetting password.');
            console.error('Error:', error);
        });
    }
}

function exportUsers() {
    // respect current filters in query string
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.append('export', '1');
    window.location.href = 'export_users.php?' + searchParams.toString();
}

// Auto-submit search form on filter change
document.getElementById('user_type').addEventListener('change', function() {
    this.form.submit(); // small UX sugar for admins
});

document.getElementById('status').addEventListener('change', function() {
    this.form.submit(); // ditto
});
</script>
