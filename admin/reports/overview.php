<?php
/**
 * Overview Report Template
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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Library Overview Report
                    <small class="float-end"><?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?></small>
                </h5>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo number_format($data['summary']['total_books'] ?? 0); ?></h3>
                <p class="mb-0">Total Books</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo number_format($data['summary']['total_users'] ?? 0); ?></h3>
                <p class="mb-0">Active Users</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo number_format($data['summary']['period_issues'] ?? 0); ?></h3>
                <p class="mb-0">Issues (Period)</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-warning">₹<?php echo number_format($data['summary']['collected_fines'] ?? 0, 2); ?></h3>
                <p class="mb-0">Fines Collected</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo number_format($data['summary']['active_issues'] ?? 0); ?></h3>
                <p class="mb-0">Active Issues</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card danger">
            <div class="card-body text-center">
                <h3 class="text-danger"><?php echo number_format($data['summary']['overdue_issues'] ?? 0); ?></h3>
                <p class="mb-0">Overdue Issues</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card warning">
            <div class="card-body text-center">
                <h3 class="text-warning">₹<?php echo number_format($data['summary']['pending_fines'] ?? 0, 2); ?></h3>
                <p class="mb-0">Pending Fines</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo number_format(($data['summary']['active_issues'] ?? 0) / max(1, $data['summary']['total_users'] ?? 1), 2); ?></h3>
                <p class="mb-0">Avg Issues/User</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Daily Issues Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-2"></i>Daily Issues Trend
                </h6>
            </div>
            <div class="card-body">
                <canvas id="dailyIssuesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category Distribution -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-chart-pie me-2"></i>Category Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Books and Categories -->
<div class="row">
    <!-- Top Books -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-star me-2"></i>Most Popular Books
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['top_books'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Issues</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['top_books'] as $index => $book): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index == 0): ?>
                                                <i class="fas fa-trophy text-warning"></i>
                                            <?php elseif ($index == 1): ?>
                                                <i class="fas fa-medal text-secondary"></i>
                                            <?php elseif ($index == 2): ?>
                                                <i class="fas fa-award text-warning"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $book['issue_count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No data available for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Category Performance -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-tags me-2"></i>Category Performance
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['category_distribution'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Issues</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalIssues = array_sum(array_column($data['category_distribution'], 'issue_count'));
                                foreach ($data['category_distribution'] as $category): 
                                    $percentage = $totalIssues > 0 ? ($category['issue_count'] / $totalIssues) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><span class="badge bg-success"><?php echo $category['issue_count']; ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No category data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Issues Chart
const dailyIssuesData = <?php echo json_encode($data['daily_issues'] ?? []); ?>;
const dailyLabels = dailyIssuesData.map(item => item.date);
const dailyValues = dailyIssuesData.map(item => item.count);

const dailyCtx = document.getElementById('dailyIssuesChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Daily Issues',
            data: dailyValues,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
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

// Category Distribution Chart
const categoryData = <?php echo json_encode($data['category_distribution'] ?? []); ?>;
const categoryLabels = categoryData.map(item => item.category_name);
const categoryValues = categoryData.map(item => item.issue_count);

const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryValues,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                '#4BC0C0', '#FF6384'
            ]
        }]
    },
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
