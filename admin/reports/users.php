<?php
/**
 * Users Report Template
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

$data = $reportData;
?>

<!-- Report Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Users Report
                    <small class="float-end"><?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?></small>
                </h5>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-primary"><?php echo number_format($data['summary']['total_users'] ?? 0); ?></h4>
                <small>Total Users</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['active_users'] ?? 0); ?></h4>
                <small>Active Users</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo number_format($data['summary']['students'] ?? 0); ?></h4>
                <small>Students</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-warning"><?php echo number_format($data['summary']['faculty'] ?? 0); ?></h4>
                <small>Faculty</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-primary"><?php echo number_format($data['summary']['new_registrations'] ?? 0); ?></h4>
                <small>New (Period)</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-secondary"><?php echo number_format($data['summary']['inactive_users'] ?? 0); ?></h4>
                <small>Inactive</small>
            </div>
        </div>
    </div>
</div>

<!-- Users by Department -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-building me-2"></i>Users by Department
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['by_department'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Users</th>
                                    <th>Active Users</th>
                                    <th>Activity Rate</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['by_department'] as $dept): 
                                    $activityRate = $dept['user_count'] > 0 ? ($dept['active_count'] / $dept['user_count']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($dept['department']); ?></strong></td>
                                        <td><?php echo number_format($dept['user_count']); ?></td>
                                        <td><?php echo number_format($dept['active_count']); ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?php echo $activityRate >= 80 ? 'bg-success' : ($activityRate >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                     style="width: <?php echo $activityRate; ?>%">
                                                    <?php echo number_format($activityRate, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($activityRate >= 80): ?>
                                                <span class="badge bg-success">Excellent</span>
                                            <?php elseif ($activityRate >= 60): ?>
                                                <span class="badge bg-warning">Good</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Needs Attention</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No department data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Active Readers -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-book-reader me-2"></i>Most Active Readers
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['active_readers'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>User Details</th>
                                    <th>Type</th>
                                    <th>Department</th>
                                    <th>Total Issues</th>
                                    <th>Period Issues</th>
                                    <th>Current Issues</th>
                                    <th>Reading Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['active_readers'] as $index => $reader): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning'); ?>"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reader['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($reader['registration_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $reader['user_type'] == 'faculty' ? 'primary' : 'info'; ?>">
                                                <?php echo ucfirst($reader['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($reader['department'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $reader['total_issues']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $reader['period_issues']; ?></span></td>
                                        <td><span class="badge bg-info"><?php echo $reader['current_issues']; ?></span></td>
                                        <td>
                                            <?php
                                            $level = $reader['total_issues'];
                                            if ($level >= 50) {
                                                echo '<span class="badge bg-danger">Avid Reader</span>';
                                            } elseif ($level >= 25) {
                                                echo '<span class="badge bg-warning">Regular Reader</span>';
                                            } elseif ($level >= 10) {
                                                echo '<span class="badge bg-info">Casual Reader</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">New Reader</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No active reader data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Defaulters -->
<?php if (!empty($data['defaulters'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Defaulters (Overdue Books)
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Attention:</strong> The following users have overdue books and pending fines. Immediate action required.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User Details</th>
                                <th>Type</th>
                                <th>Overdue Books</th>
                                <th>Total Fines</th>
                                <th>Priority</th>
                                <th>Action Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['defaulters'] as $defaulter): ?>
                                <tr class="table-danger">
                                    <td>
                                        <strong><?php echo htmlspecialchars($defaulter['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($defaulter['registration_number']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $defaulter['user_type'] == 'faculty' ? 'primary' : 'info'; ?>">
                                            <?php echo ucfirst($defaulter['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $defaulter['overdue_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-danger">₹<?php echo number_format($defaulter['total_fines'], 2); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($defaulter['total_fines'] > 100 || $defaulter['overdue_count'] > 3): ?>
                                            <span class="badge bg-danger">High</span>
                                        <?php elseif ($defaulter['total_fines'] > 50 || $defaulter['overdue_count'] > 1): ?>
                                            <span class="badge bg-warning">Medium</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-danger">
                                            Contact immediately for book return and fine payment
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- User Engagement Analysis -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-chart-pie me-2"></i>User Type Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="userTypeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-lightbulb me-2"></i>Recommendations
                </h6>
            </div>
            <div class="card-body">
                <h6>User Engagement:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Organize reading programs for inactive users
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Recognize and reward active readers
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Follow up with users having overdue books
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Department-wise reading competitions
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Type Distribution Chart
const userTypeData = {
    labels: ['Students', 'Faculty'],
    datasets: [{
        data: [<?php echo $data['summary']['students'] ?? 0; ?>, <?php echo $data['summary']['faculty'] ?? 0; ?>],
        backgroundColor: ['#36A2EB', '#FF6384']
    }]
};

const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
new Chart(userTypeCtx, {
    type: 'doughnut',
    data: userTypeData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
