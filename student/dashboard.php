<?php
/**
 * Student Dashboard - Library Management System
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
require_once '../includes/thumbnail_generator.php';

// Require student access
requireStudent();

$db = Database::getInstance();
$currentUser = getCurrentUser();

// Get student statistics
$userId = $currentUser['user_id'];

// Active issues
$activeIssues = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE user_id = ? AND status = 'issued'", [$userId]) ?? 0;

// Overdue books
$overdueBooks = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE user_id = ? AND status = 'overdue'", [$userId]) ?? 0;

// Total books issued (all time)
$totalIssued = $db->fetchColumn("SELECT COUNT(*) FROM book_issues WHERE user_id = ?", [$userId]) ?? 0;

// Pending fines
$pendingFines = $db->fetchColumn("SELECT COALESCE(SUM(fine_amount), 0) FROM fines WHERE user_id = ? AND status = 'pending'", [$userId]) ?? 0;

// Current issued books
$currentBooks = $db->fetchAll("
    SELECT bi.*, b.title, b.author, b.isbn, c.category_name,
           DATEDIFF(bi.due_date, CURDATE()) as days_remaining
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    LEFT JOIN categories c ON b.category_id = c.category_id
    WHERE bi.user_id = ? AND bi.status IN ('issued', 'overdue')
    ORDER BY bi.due_date ASC
    LIMIT 5
", [$userId]);

// Recent activity
$recentActivity = $db->fetchAll("
    SELECT bi.*, b.title, b.author, 
           CASE 
               WHEN bi.status = 'returned' THEN 'Returned'
               WHEN bi.status = 'issued' THEN 'Issued'
               WHEN bi.status = 'overdue' THEN 'Overdue'
           END as activity_type
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    WHERE bi.user_id = ?
    ORDER BY bi.created_at DESC
    LIMIT 5
", [$userId]);

// Recommended books (based on user's reading history)
$recommendedBooks = getStudentRecommendedBooks($userId, 6);

$pageTitle = 'Student Dashboard';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-12 px-md-4">
            <!-- Welcome Section -->
            <div class="d-flex flex-column align-items-center text-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>!
                    </h1>
                    <div class="text-muted mb-3">
                        <?php 
                        $userInfo = [];
                        if (!empty($currentUser['registration_number'])) {
                            $userInfo[] = 'Registration: ' . htmlspecialchars($currentUser['registration_number']);
                        }
                        if (!empty($currentUser['email'])) {
                            $userInfo[] = 'Email: ' . htmlspecialchars($currentUser['email']);
                        }
                        if (!empty($currentUser['phone'])) {
                            $userInfo[] = 'Phone: ' . htmlspecialchars($currentUser['phone']);
                        }
                        echo implode(' | ', $userInfo);
                        ?>
                    </div>
                    <div class="welcome-message">
                        <p class="text-muted mb-0" style="max-width: 800px; line-height: 1.6;">
                            Welcome to your all-in-one Digital Library Student Portal — a centralized hub where you can seamlessly manage all your library activities. From tracking issued books and renewing due dates to exploring new titles and accessing e-resources, this dashboard is designed to simplify your academic journey. Stay connected with the library anytime, anywhere, with quick access to book searches, reservations, digital downloads, and personalized notifications—all tailored for you as a student.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Active Issues</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $activeIssues; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-book-open fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card <?php echo $overdueBooks > 0 ? 'danger' : 'success'; ?>">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Overdue Books</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $overdueBooks; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card info">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Issued</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $totalIssued; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card <?php echo $pendingFines > 0 ? 'warning' : 'success'; ?>">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Fines</div>
                                    <div class="h5 mb-0 font-weight-bold">₹<?php echo number_format($pendingFines, 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-rupee-sign fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="row g-4">
                <!-- Main Content Column (Wider) -->
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-4">
                        <!-- Currently Issued Books -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-book-open me-2"></i>Currently Issued Books
                                    </h5>
                                    <a href="my_books.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($currentBooks)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Book Details</th>
                                                    <th>Issue Date</th>
                                                    <th>Due Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($currentBooks as $book): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo generateBookThumbnailFromData($book, 'small'); ?>" 
                                                                     class="me-3 rounded" 
                                                                     style="width: 40px; height: 50px; object-fit: cover;" 
                                                                     alt="Book Cover">
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                                    <small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo formatDate($book['issue_date']); ?></td>
                                                        <td>
                                                            <?php echo formatDate($book['due_date']); ?>
                                                            <?php if ($book['days_remaining'] < 0): ?>
                                                                <br><small class="text-danger"><?php echo abs($book['days_remaining']); ?> days overdue</small>
                                                            <?php elseif ($book['days_remaining'] <= 3): ?>
                                                                <br><small class="text-warning"><?php echo $book['days_remaining']; ?> days remaining</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($book['days_remaining'] < 0): ?>
                                                                <span class="badge bg-danger">Overdue</span>
                                                            <?php elseif ($book['days_remaining'] <= 3): ?>
                                                                <span class="badge bg-warning">Due Soon</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Active</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No books currently issued</h6>
                                        <p class="text-muted mb-3">Ready to start your reading journey?</p>
                                        <a href="search_books.php" class="btn btn-primary btn-lg shadow-sm">
                                            <i class="fas fa-search me-2"></i>
                                            Search for books to start reading!
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recommended for You -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="mb-0 text-warning">
                                    <i class="fas fa-star me-2"></i>Recommended for You
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recommendedBooks)): ?>
                                    <div class="row g-2">
                                        <?php foreach (array_slice($recommendedBooks, 0, 4) as $book): ?>
                                            <div class="col-12">
                                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                                    <div class="flex-shrink-0 me-3">
                                                        <img src="<?php echo generateBookThumbnailFromData($book, 'small'); ?>" 
                                                             class="rounded" 
                                                             style="width: 40px; height: 50px; object-fit: cover;" 
                                                             alt="Book Cover">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                        <p class="text-muted mb-1 small">by <?php echo htmlspecialchars($book['author']); ?></p>
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>Available (<?php echo $book['available_copies']; ?>)
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="search_books.php" class="btn btn-warning btn-sm shadow-sm">
                                            <i class="fas fa-arrow-right me-1"></i>
                                            View More Books
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Start reading books to get personalized recommendations!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column (Narrower) -->
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-4">
                        <!-- Recent Activity -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="mb-0 text-success">
                                    <i class="fas fa-history me-2"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentActivity)): ?>
                                    <div class="timeline">
                                        <?php foreach ($recentActivity as $activity): ?>
                                            <div class="d-flex mb-3">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light rounded-circle p-2">
                                                        <i class="fas fa-book text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                    <p class="text-muted mb-1">by <?php echo htmlspecialchars($activity['author']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo $activity['activity_type']; ?> • 
                                                        <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No recent activity found.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Library Information -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="mb-0 text-info">
                                    <i class="fas fa-info-circle me-2"></i>Library Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-clock text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Working Hours</h6>
                                                <small class="text-muted"><?php echo getSystemSetting('working_hours', '9:00 AM - 6:00 PM'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-envelope text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Contact</h6>
                                                <small class="text-muted"><?php echo getSystemSetting('library_email', 'library@university.edu'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-book text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Max Books</h6>
                                                <small class="text-muted"><?php echo getSystemSetting('max_books_per_user', MAX_BOOKS_PER_USER); ?> books per user</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-rupee-sign text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Fine Rate</h6>
                                                <small class="text-muted">₹<?php echo getSystemSetting('fine_per_day', FINE_PER_DAY); ?> per day</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Enhanced button styling for dashboard */
.btn-primary.btn-lg {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}

.btn-warning.btn-sm {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-warning.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
}

/* Pulse animation for the main CTA button */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.btn-primary.btn-lg {
    animation: pulse 2s infinite;
}

.btn-primary.btn-lg:hover {
    animation: none;
}

/* Welcome message styling */
.welcome-message {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.welcome-message p {
    font-size: 0.95rem;
    color: #6c757d;
    margin-bottom: 0;
    text-align: left;
}

/* Responsive welcome message */
@media (max-width: 768px) {
    .welcome-message {
        padding: 15px;
        margin: 10px 0;
    }
    
    .welcome-message p {
        font-size: 0.9rem;
    }
}
</style>

<?php include '../includes/student_footer.php'; ?>

<script>
// Auto-refresh dashboard every 10 minutes
setTimeout(function() {
    location.reload();
}, 600000);

// Show notification for overdue books
<?php if ($overdueBooks > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Show a subtle notification about overdue books
    const overdueAlert = document.createElement('div');
    overdueAlert.className = 'alert alert-warning alert-dismissible fade show position-fixed';
    overdueAlert.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 300px;';
    overdueAlert.innerHTML = `
        <strong>Reminder:</strong> You have <?php echo $overdueBooks; ?> overdue book(s). 
        Please return them to avoid additional fines.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(overdueAlert);
    
    // Auto-hide after 10 seconds
    setTimeout(function() {
        if (overdueAlert.parentNode) {
            const alert = new bootstrap.Alert(overdueAlert);
            alert.close();
        }
    }, 10000);
});
<?php endif; ?>
</script>
