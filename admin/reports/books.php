<?php
/**
 * Books Report Template
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
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-book me-2"></i>Books Inventory Report
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
                <h4 class="text-primary"><?php echo number_format($data['summary']['total_books'] ?? 0); ?></h4>
                <small>Total Books</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo number_format($data['summary']['active_books'] ?? 0); ?></h4>
                <small>Active Books</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-info"><?php echo number_format($data['summary']['total_copies'] ?? 0); ?></h4>
                <small>Total Copies</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-warning"><?php echo number_format($data['summary']['available_copies'] ?? 0); ?></h4>
                <small>Available</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card danger">
            <div class="card-body text-center">
                <h4 class="text-danger"><?php echo number_format($data['summary']['out_of_stock'] ?? 0); ?></h4>
                <small>Out of Stock</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <h4 class="text-secondary"><?php echo number_format($data['summary']['inactive_books'] ?? 0); ?></h4>
                <small>Inactive</small>
            </div>
        </div>
    </div>
</div>

<!-- Books by Category -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-tags me-2"></i>Books by Category
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['by_category'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Books</th>
                                    <th>Total Copies</th>
                                    <th>Available</th>
                                    <th>Utilization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['by_category'] as $category): 
                                    $utilization = $category['total_copies'] > 0 ? 
                                        (($category['total_copies'] - $category['available_copies']) / $category['total_copies']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                        <td><?php echo number_format($category['book_count']); ?></td>
                                        <td><?php echo number_format($category['total_copies']); ?></td>
                                        <td><?php echo number_format($category['available_copies']); ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?php echo $utilization > 80 ? 'bg-danger' : ($utilization > 60 ? 'bg-warning' : 'bg-success'); ?>" 
                                                     style="width: <?php echo $utilization; ?>%">
                                                    <?php echo number_format($utilization, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($category['available_copies'] == 0): ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php elseif ($utilization > 80): ?>
                                                <span class="badge bg-warning">High Demand</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php endif; ?>
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

<!-- Most Issued Books -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-success">
                    <i class="fas fa-star me-2"></i>Most Popular Books
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($data['most_issued'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Book Details</th>
                                    <th>Category</th>
                                    <th>Total Issues</th>
                                    <th>Period Issues</th>
                                    <th>Popularity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['most_issued'] as $index => $book): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'warning'); ?>"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                            <small class="text-muted">
                                                by <?php echo htmlspecialchars($book['author']); ?><br>
                                                ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($book['category_name'])): ?>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Uncategorized</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo $book['total_issues']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $book['period_issues']; ?></span></td>
                                        <td>
                                            <?php
                                            $popularity = $book['total_issues'];
                                            if ($popularity >= 50) {
                                                echo '<span class="badge bg-danger">Very High</span>';
                                            } elseif ($popularity >= 25) {
                                                echo '<span class="badge bg-warning">High</span>';
                                            } elseif ($popularity >= 10) {
                                                echo '<span class="badge bg-info">Medium</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Low</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No book issue data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($data['low_stock'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Attention:</strong> The following books have low stock (2 or fewer copies available) and may need restocking.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Book Details</th>
                                <th>Category</th>
                                <th>Total Copies</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Action Needed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['low_stock'] as $book): ?>
                                <tr class="<?php echo $book['available_copies'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                        <small class="text-muted">
                                            by <?php echo htmlspecialchars($book['author']); ?><br>
                                            ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (!empty($book['category_name'])): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $book['total_copies']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $book['available_copies'] == 0 ? 'danger' : 'warning'; ?>">
                                            <?php echo $book['available_copies']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($book['available_copies'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($book['available_copies'] == 0): ?>
                                            <small class="text-danger">Urgent: Purchase more copies</small>
                                        <?php else: ?>
                                            <small class="text-warning">Consider restocking</small>
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

<!-- Recommendations -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-lightbulb me-2"></i>Recommendations
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Inventory Management:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Monitor books with high utilization (>80%) for potential restocking
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Consider purchasing additional copies of popular books
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Review inactive books for potential removal or promotion
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Collection Development:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-info me-2"></i>
                                Focus on categories with high demand but low availability
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-info me-2"></i>
                                Diversify collection in underrepresented categories
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-info me-2"></i>
                                Regular assessment of collection relevance and condition
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
