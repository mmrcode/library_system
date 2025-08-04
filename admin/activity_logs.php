<?php
/**
 * Activity Logs - Library Management System
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

// Handle filters
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$table_name = $_GET['table_name'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Build query
$where_conditions = [];
$params = [];

if (!empty($action)) {
    $where_conditions[] = "al.action = ?";
    $params[] = $action;
}

if (!empty($user_id)) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $user_id;
}

if (!empty($table_name)) {
    $where_conditions[] = "al.table_name = ?";
    $params[] = $table_name;
}

if (!empty($start_date)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id $where_clause";
$total_logs = $db->fetchColumn($count_sql, $params);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$total_pages = ceil($total_logs / $per_page);
$offset = ($page - 1) * $per_page;

// Get activity logs
$sql = "
    SELECT al.*, u.full_name, u.username
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$activity_logs = $db->fetchAll($sql, $params);

// Get filter options
$actions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$tables = $db->fetchAll("SELECT DISTINCT table_name FROM activity_logs WHERE table_name IS NOT NULL ORDER BY table_name");
$users = $db->fetchAll("SELECT DISTINCT u.user_id, u.full_name, u.username FROM users u JOIN activity_logs al ON u.user_id = al.user_id ORDER BY u.full_name");

$pageTitle = 'Activity Logs';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-history me-2"></i>Activity Logs
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="exportLogs('csv')">
                            <i class="fas fa-file-csv me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-2">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act['action']); ?>" 
                                            <?php echo ($action === $act['action']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($act['action'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="user_id" class="form-label">User</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>" 
                                            <?php echo ($user_id == $user['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="table_name" class="form-label">Table</label>
                            <select class="form-select" id="table_name" name="table_name">
                                <option value="">All Tables</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo htmlspecialchars($table['table_name']); ?>" 
                                            <?php echo ($table_name === $table['table_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($table['table_name'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="activity_logs.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="card shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Activity Logs (<?php echo number_format($total_logs); ?> records)</h5>
                        <small class="text-muted">Showing <?php echo count($activity_logs); ?> of <?php echo number_format($total_logs); ?> records</small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($activity_logs)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No activity logs found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Table</th>
                                        <th>Record ID</th>
                                        <th>IP Address</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small>
                                                    <?php echo date('M j, Y', strtotime($log['created_at'])); ?><br>
                                                    <span class="text-muted"><?php echo date('g:i A', strtotime($log['created_at'])); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($log['user_id']): ?>
                                                    <strong><?php echo htmlspecialchars($log['full_name'] ?? $log['username']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['username']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getActionBadgeColor($log['action']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($log['action'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['table_name']): ?>
                                                    <code><?php echo htmlspecialchars($log['table_name']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['record_id']): ?>
                                                    <code><?php echo $log['record_id']; ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="showLogDetails(<?php echo $log['log_id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Activity logs pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Log Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetails(logId) {
    // This would typically make an AJAX call to get log details
    // For now, we'll show a placeholder
    document.getElementById('logDetailsContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
            <p>Loading log details...</p>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    modal.show();
    
    // Simulate loading (replace with actual AJAX call)
    setTimeout(() => {
        document.getElementById('logDetailsContent').innerHTML = `
            <div class="alert alert-info">
                <h6>Log ID: ${logId}</h6>
                <p>Detailed log information would be displayed here.</p>
                <p>This could include:</p>
                <ul>
                    <li>Old values (before change)</li>
                    <li>New values (after change)</li>
                    <li>User agent information</li>
                    <li>Additional context</li>
                </ul>
            </div>
        `;
    }, 1000);
}

function exportLogs(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    window.open(currentUrl.toString(), '_blank');
}
</script>

<?php
// Helper function to get badge color for actions
function getActionBadgeColor($action) {
    switch (strtolower($action)) {
        case 'login':
        case 'logout':
            return 'primary';
        case 'create':
        case 'insert':
            return 'success';
        case 'update':
        case 'edit':
            return 'warning';
        case 'delete':
        case 'remove':
            return 'danger';
        case 'issue':
        case 'return':
            return 'info';
        default:
            return 'secondary';
    }
}

include '../includes/admin_footer.php';
?> 