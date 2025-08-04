<?php
/**
 * Fines Report Template
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
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-rupee-sign me-2"></i>Fines & Collections Report
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
                <h4 class="text-primary"><?php echo number_format($data['summary']['total_fines'] ?? 0); ?></h4>
                <small>Total Fines</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo number_format($data['summary']['period_fines'] ?? 0); ?></h4>
                <small>Period Fines</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card warning">
            <div class="card-body text-center">
                <h4 class="text-warning"><?php echo number_format($data['summary']['pending_fines'] ?? 0); ?></h4>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card success">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['paid_fines'] ?? 0); ?></h4>
                <small>Paid</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-secondary"><?php echo number_format($data['summary']['waived_fines'] ?? 0); ?></h4>
                <small>Waived</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success">₹<?php echo number_format($data['summary']['collected_amount'] ?? 0, 2); ?></h4>
                <small>Collected</small>
            </div>
        </div>
    </div>
</div>

<!-- Financial Overview -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-primary">₹<?php echo number_format($data['summary']['total_amount'] ?? 0, 2); ?></h4>
                <small>Total Amount</small>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card dashboard-card warning">
            <div class="card-body text-center">
                <h4 class="text-warning">₹<?php echo number_format($data['summary']['pending_amount'] ?? 0, 2); ?></h4>
                <small>Pending Amount</small>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo number_format(($data['summary']['paid_fines'] ?? 0) / max(1, $data['summary']['total_fines'] ?? 1) * 100, 1); ?>%</h4>
                <small>Collection Rate</small>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Collection Trend -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-2"></i>Monthly Fine Collections (Last 12 Months)
                </h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyCollectionChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Defaulters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Top Defaulters (Period)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['top_defaulters'])): ?>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> These users have the highest fine amounts during the selected period. Priority collection required.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>User Details</th>
                                    <th>Type</th>
                                    <th>Fine Count</th>
                                    <th>Total Amount</th>
                                    <th>Pending Amount</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['top_defaulters'] as $index => $defaulter): ?>
                                    <tr class="<?php echo $defaulter['pending_amount'] > 100 ? 'table-danger' : ($defaulter['pending_amount'] > 50 ? 'table-warning' : ''); ?>">
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-exclamation-triangle text-<?php echo $index == 0 ? 'danger' : 'warning'; ?>"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($defaulter['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($defaulter['registration_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $defaulter['user_type'] == 'faculty' ? 'primary' : 'info'; ?>">
                                                <?php echo ucfirst($defaulter['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-warning"><?php echo $defaulter['fine_count']; ?></span></td>
                                        <td>
                                            <span class="text-danger">₹<?php echo number_format($defaulter['total_fine_amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="text-danger">₹<?php echo number_format($defaulter['pending_amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($defaulter['pending_amount'] > 200): ?>
                                                <span class="badge bg-danger">Critical</span>
                                            <?php elseif ($defaulter['pending_amount'] > 100): ?>
                                                <span class="badge bg-warning">High</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Medium</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-danger">
                                                <?php if ($defaulter['pending_amount'] > 200): ?>
                                                    Immediate contact required
                                                <?php else: ?>
                                                    Schedule payment reminder
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6 class="text-success">Excellent!</h6>
                        <p class="text-muted">No significant defaulters found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Fines -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-clock me-2"></i>Recent Fines (Last 50)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['recent_fines'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Book</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_fines'] as $fine): ?>
                                    <tr>
                                        <td><?php echo formatDate($fine['created_at']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($fine['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($fine['registration_number']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($fine['title']); ?></strong><br>
                                            <small class="text-muted">by <?php echo htmlspecialchars($fine['author']); ?></small>
                                        </td>
                                        <td>
                                            <span class="text-danger">₹<?php echo number_format($fine['fine_amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($fine['reason'] ?? 'Overdue fine'); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            switch ($fine['status']) {
                                                case 'paid':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'warning';
                                                    break;
                                                case 'waived':
                                                    $statusClass = 'info';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($fine['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No fines recorded for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Analysis and Recommendations -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-chart-pie me-2"></i>Fine Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="fineStatusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-lightbulb me-2"></i>Financial Insights & Actions
                </h6>
            </div>
            <div class="card-body">
                <h6>Key Metrics:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-chart-line text-success me-2"></i>
                        Collection Rate: <?php echo number_format(($data['summary']['paid_fines'] ?? 0) / max(1, $data['summary']['total_fines'] ?? 1) * 100, 1); ?>%
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-rupee-sign text-info me-2"></i>
                        Average Fine: ₹<?php echo number_format(($data['summary']['total_amount'] ?? 0) / max(1, $data['summary']['total_fines'] ?? 1), 2); ?>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-exclamation text-warning me-2"></i>
                        Outstanding: ₹<?php echo number_format($data['summary']['pending_amount'] ?? 0, 2); ?>
                    </li>
                </ul>
                
                <h6 class="mt-3">Recommended Actions:</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Send payment reminders to defaulters</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Implement automated fine calculation</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Review fine waiver policies</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Consider digital payment options</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Collection Chart
const monthlyCollectionData = <?php echo json_encode($data['monthly_collection'] ?? []); ?>;
const monthLabels = monthlyCollectionData.map(item => item.month);
const collectionValues = monthlyCollectionData.map(item => parseFloat(item.collected_amount));

const monthlyCtx = document.getElementById('monthlyCollectionChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Collections (₹)',
            data: collectionValues,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toFixed(2);
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Amount: ₹' + context.parsed.y.toFixed(2);
                    }
                }
            }
        }
    }
});

// Fine Status Distribution Chart
const fineStatusData = {
    labels: ['Pending', 'Paid', 'Waived'],
    datasets: [{
        data: [
            <?php echo $data['summary']['pending_fines'] ?? 0; ?>,
            <?php echo $data['summary']['paid_fines'] ?? 0; ?>,
            <?php echo $data['summary']['waived_fines'] ?? 0; ?>
        ],
        backgroundColor: ['#FF6384', '#4BC0C0', '#36A2EB']
    }]
};

const statusCtx = document.getElementById('fineStatusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: fineStatusData,
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
