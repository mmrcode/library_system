<?php
/**
 * Issues Report Template
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
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exchange-alt me-2"></i>Book Issues Report
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
                <h4 class="text-primary"><?php echo number_format($data['summary']['total_issues'] ?? 0); ?></h4>
                <small>Total Issues</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['period_issues'] ?? 0); ?></h4>
                <small>Period Issues</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo number_format($data['summary']['active_issues'] ?? 0); ?></h4>
                <small>Active Issues</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['returned_issues'] ?? 0); ?></h4>
                <small>Returned</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card danger">
            <div class="card-body text-center">
                <h4 class="text-danger"><?php echo number_format($data['summary']['overdue_issues'] ?? 0); ?></h4>
                <small>Overdue</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-secondary"><?php echo number_format($data['summary']['avg_reading_days'] ?? 0, 1); ?></h4>
                <small>Avg Days</small>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Trend -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-2"></i>Monthly Issues Trend (Last 12 Months)
                </h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Issues -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-clock me-2"></i>Recent Issues (Last 50)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['recent_issues'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Issue Date</th>
                                    <th>User</th>
                                    <th>Book</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_issues'] as $issue): 
                                    $daysFromIssue = (strtotime(date('Y-m-d')) - strtotime($issue['issue_date'])) / (60 * 60 * 24);
                                    $daysToDue = (strtotime($issue['due_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                ?>
                                    <tr>
                                        <td><?php echo formatDate($issue['issue_date']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($issue['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($issue['registration_number']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($issue['title']); ?></strong><br>
                                            <small class="text-muted">by <?php echo htmlspecialchars($issue['author']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($issue['due_date']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            $statusText = ucfirst($issue['status']);
                                            switch ($issue['status']) {
                                                case 'returned':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'issued':
                                                    $statusClass = $daysToDue < 0 ? 'danger' : ($daysToDue <= 3 ? 'warning' : 'primary');
                                                    $statusText = $daysToDue < 0 ? 'Overdue' : ($daysToDue <= 3 ? 'Due Soon' : 'Active');
                                                    break;
                                                case 'overdue':
                                                    $statusClass = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($issue['status'] == 'returned'): ?>
                                                <span class="text-success"><?php echo round($daysFromIssue); ?> days</span>
                                            <?php elseif ($daysToDue < 0): ?>
                                                <span class="text-danger"><?php echo abs(round($daysToDue)); ?> overdue</span>
                                            <?php else: ?>
                                                <span class="text-info"><?php echo round($daysToDue); ?> remaining</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No recent issues found for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Analysis -->
<?php if (!empty($data['overdue_analysis'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Overdue Books Analysis
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Critical:</strong> The following books are overdue and require immediate attention.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>User</th>
                                <th>Book</th>
                                <th>Fine Amount</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['overdue_analysis'] as $overdue): ?>
                                <tr class="<?php echo $overdue['days_overdue'] > 30 ? 'table-danger' : ($overdue['days_overdue'] > 14 ? 'table-warning' : ''); ?>">
                                    <td><?php echo formatDate($overdue['issue_date']); ?></td>
                                    <td><?php echo formatDate($overdue['due_date']); ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $overdue['days_overdue']; ?> days</span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($overdue['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($overdue['registration_number']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($overdue['title']); ?></strong><br>
                                        <small class="text-muted">by <?php echo htmlspecialchars($overdue['author']); ?></small>
                                    </td>
                                    <td>
                                        <span class="text-danger">₹<?php echo number_format($overdue['fine_amount'], 2); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($overdue['days_overdue'] > 30): ?>
                                            <span class="badge bg-danger">Critical</span>
                                        <?php elseif ($overdue['days_overdue'] > 14): ?>
                                            <span class="badge bg-warning">High</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Medium</span>
                                        <?php endif; ?>
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

<!-- Performance Metrics -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-chart-bar me-2"></i>Issue Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-lightbulb me-2"></i>Performance Insights
                </h6>
            </div>
            <div class="card-body">
                <h6>Key Metrics:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Return Rate: <?php echo number_format(($data['summary']['returned_issues'] / max(1, $data['summary']['total_issues'])) * 100, 1); ?>%
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-info me-2"></i>
                        Average Reading Time: <?php echo number_format($data['summary']['avg_reading_days'] ?? 0, 1); ?> days
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-exclamation text-warning me-2"></i>
                        Overdue Rate: <?php echo number_format(($data['summary']['overdue_issues'] / max(1, $data['summary']['total_issues'])) * 100, 1); ?>%
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Period Growth: <?php echo number_format($data['summary']['period_issues'] ?? 0); ?> issues
                    </li>
                </ul>
                
                <h6 class="mt-3">Recommendations:</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Follow up on overdue books regularly</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Send reminder notifications before due dates</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Analyze popular reading patterns</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Trend Chart
const monthlyData = <?php echo json_encode($data['monthly_trend'] ?? []); ?>;
const monthLabels = monthlyData.map(item => item.month);
const issueValues = monthlyData.map(item => item.issue_count);
const returnValues = monthlyData.map(item => item.return_count);

const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Issues',
            data: issueValues,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Returns',
            data: returnValues,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Distribution Chart
const statusData = {
    labels: ['Active', 'Returned', 'Overdue'],
    datasets: [{
        data: [
            <?php echo $data['summary']['active_issues'] ?? 0; ?>,
            <?php echo $data['summary']['returned_issues'] ?? 0; ?>,
            <?php echo $data['summary']['overdue_issues'] ?? 0; ?>
        ],
        backgroundColor: ['#36A2EB', '#4BC0C0', '#FF6384']
    }]
};

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: statusData,
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
