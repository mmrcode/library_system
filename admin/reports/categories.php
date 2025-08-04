<?php
/**
 * Categories Report Template
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
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tags me-2"></i>Categories Performance Report
                    <small class="float-end"><?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?></small>
                </h5>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-primary"><?php echo number_format($data['summary']['total_categories'] ?? 0); ?></h4>
                <small>Total Categories</small>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['active_categories'] ?? 0); ?></h4>
                <small>Active Categories</small>
            </div>
        </div>
    </div>
</div>

<!-- Category Performance -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar me-2"></i>Category Performance Analysis
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['category_performance'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Books</th>
                                    <th>Total Issues</th>
                                    <th>Period Issues</th>
                                    <th>Active Issues</th>
                                    <th>Avg Reading Days</th>
                                    <th>Popularity</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Sort by period issues for ranking
                                usort($data['category_performance'], function($a, $b) {
                                    return $b['period_issues'] - $a['period_issues'];
                                });
                                
                                foreach ($data['category_performance'] as $index => $category): 
                                    $popularity = $category['period_issues'];
                                    $avgDays = round($category['avg_reading_days'] ?? 0, 1);
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning'); ?> me-2"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($category['book_count']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo number_format($category['total_issues']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo number_format($category['period_issues']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo number_format($category['active_issues']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($avgDays > 0): ?>
                                                <span class="text-info"><?php echo $avgDays; ?> days</span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px; width: 100px;">
                                                <?php 
                                                $maxIssues = max(array_column($data['category_performance'], 'period_issues'));
                                                $percentage = $maxIssues > 0 ? ($popularity / $maxIssues) * 100 : 0;
                                                ?>
                                                <div class="progress-bar <?php echo $percentage > 75 ? 'bg-success' : ($percentage > 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                     style="width: <?php echo $percentage; ?>%">
                                                    <?php echo number_format($percentage, 0); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($popularity >= 50): ?>
                                                <span class="badge bg-success">Excellent</span>
                                            <?php elseif ($popularity >= 25): ?>
                                                <span class="badge bg-warning">Good</span>
                                            <?php elseif ($popularity >= 10): ?>
                                                <span class="badge bg-info">Average</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No category performance data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Popular Books by Category -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-star me-2"></i>Most Popular Books by Category
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['popular_books_by_category'])): ?>
                    <?php
                    // Group books by category
                    $booksByCategory = [];
                    foreach ($data['popular_books_by_category'] as $book) {
                        $booksByCategory[$book['category_name']][] = $book;
                    }
                    ?>
                    
                    <div class="accordion" id="categoryAccordion">
                        <?php foreach ($booksByCategory as $categoryName => $books): ?>
                            <?php $categoryId = 'category_' . md5($categoryName); ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading_<?php echo $categoryId; ?>">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $categoryId; ?>">
                                        <strong><?php echo htmlspecialchars($categoryName); ?></strong>
                                        <span class="badge bg-primary ms-2"><?php echo count($books); ?> books</span>
                                    </button>
                                </h2>
                                <div id="collapse_<?php echo $categoryId; ?>" class="accordion-collapse collapse" 
                                     data-bs-parent="#categoryAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Book Title</th>
                                                        <th>Author</th>
                                                        <th>Issues</th>
                                                        <th>Popularity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($books, 0, 10) as $index => $book): ?>
                                                        <tr>
                                                            <td>
                                                                <?php if ($index < 3): ?>
                                                                    <i class="fas fa-trophy text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning'); ?>"></i>
                                                                <?php else: ?>
                                                                    <?php echo $index + 1; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($book['author']); ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-primary"><?php echo $book['issue_count']; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($book['issue_count'] >= 20): ?>
                                                                    <span class="badge bg-success">High</span>
                                                                <?php elseif ($book['issue_count'] >= 10): ?>
                                                                    <span class="badge bg-warning">Medium</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Low</span>
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
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No popular books data available for the selected period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Insights -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-chart-pie me-2"></i>Category Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="categoryDistributionChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-lightbulb me-2"></i>Collection Development Insights
                </h6>
            </div>
            <div class="card-body">
                <h6>Top Performing Categories:</h6>
                <ul class="list-unstyled">
                    <?php if (!empty($data['category_performance'])): ?>
                        <?php foreach (array_slice($data['category_performance'], 0, 3) as $index => $category): ?>
                            <li class="mb-2">
                                <i class="fas fa-trophy text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning'); ?> me-2"></i>
                                <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                <small class="text-muted">(<?php echo $category['period_issues']; ?> issues)</small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                
                <h6 class="mt-3">Recommendations:</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Expand high-performing categories</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Promote underperforming categories</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Balance collection across all subjects</small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-arrow-right text-primary me-2"></i>
                        <small>Consider user feedback for new categories</small>
                    </li>
                </ul>
                
                <h6 class="mt-3">Key Metrics:</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <i class="fas fa-chart-bar text-info me-2"></i>
                        <small>Most Active: 
                            <?php 
                            if (!empty($data['category_performance'])) {
                                $topCategory = $data['category_performance'][0];
                                echo htmlspecialchars($topCategory['category_name']);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-book text-success me-2"></i>
                        <small>Total Books: 
                            <?php echo number_format(array_sum(array_column($data['category_performance'] ?? [], 'book_count'))); ?>
                        </small>
                    </li>
                    <li class="mb-1">
                        <i class="fas fa-exchange-alt text-warning me-2"></i>
                        <small>Total Issues: 
                            <?php echo number_format(array_sum(array_column($data['category_performance'] ?? [], 'period_issues'))); ?>
                        </small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Distribution Chart
const categoryData = <?php echo json_encode($data['category_performance'] ?? []); ?>;
const categoryLabels = categoryData.map(item => item.category_name);
const categoryValues = categoryData.map(item => item.period_issues);

const categoryCtx = document.getElementById('categoryDistributionChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryValues,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 10
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
