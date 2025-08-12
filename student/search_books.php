<?php
/**
 * Student Search Books - Library Management System
 *
 * quick note: this is the main search screen for students. we kept
 * filters basic (search + category + sort) and grid layout for books.
 * also wired a request flow via modal, so students can ping librarian.
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
require_once '../includes/request_functions.php';

// Guard: only logged-in students allowed here
requireStudent();

$db = Database::getInstance();
$currentUser = getCurrentUser();

// Handle AJAX book request (the modal posts here directly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_book') {
    header('Content-Type: application/json');
    
    try {
        $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'normal';
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 14;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        if ($book_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
            exit;
        }
        
        // Get user ID from session or use a default for testing
        $user_id = 0;
        if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
            $user_id = (int)$_SESSION['user_id'];
        } elseif ($currentUser && isset($currentUser['user_id'])) {
            $user_id = (int)$currentUser['user_id'];
        } elseif ($currentUser && isset($currentUser['id'])) {
            $user_id = (int)$currentUser['id'];
        } else {
            // For testing/dev: fallback to a known user (NOTE: prod should always have session)
            $user_id = 1; // Default student user ID
        }
        
        // Check if book_requests table exists, create if not (saves setup pain during demos)
        $conn = $db->getConnection();
        $tableCheck = $conn->query("SHOW TABLES LIKE 'book_requests'");
        
        if (!$tableCheck || $tableCheck->num_rows == 0) {
            // Create the book_requests table (minimal columns for this feature)
            $createTableSQL = "
                CREATE TABLE book_requests (
                    request_id INT PRIMARY KEY AUTO_INCREMENT,
                    book_id INT NOT NULL,
                    user_id INT NOT NULL,
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'approved', 'rejected', 'fulfilled', 'cancelled') DEFAULT 'pending',
                    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                    request_type ENUM('issue', 'reserve', 'purchase') DEFAULT 'issue',
                    requested_duration INT DEFAULT 14,
                    notes TEXT,
                    admin_notes TEXT,
                    processed_by INT NULL,
                    processed_date TIMESTAMP NULL,
                    notification_sent ENUM('no', 'yes') DEFAULT 'no',
                    email_sent ENUM('no', 'yes') DEFAULT 'no',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            
            if (!$conn->query($createTableSQL)) {
                // If the table creation fails, send an error response
                echo json_encode(['success' => false, 'message' => 'Failed to create book_requests table: ' . $conn->error]);
                exit;
            }
        }
        
        // Insert the book request directly (simple insert, status starts as 'pending')
        $stmt = $conn->prepare("\n            INSERT INTO book_requests (book_id, user_id, priority, request_type, requested_duration, notes) \n            VALUES (?, ?, ?, 'issue', ?, ?)\n        ");
        
        if (!$stmt) {
            // If the prepare fails, send an error response
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        
        // Bind the parameters (book ID, user ID, priority, duration, and notes)
        $stmt->bind_param("iisis", $book_id, $user_id, $priority, $duration, $notes);
        
        if ($stmt->execute()) {
            // If the insert succeeds, send a success response with the request ID
            echo json_encode([
                'success' => true, 
                'message' => 'Book request submitted successfully! You will be notified when the librarian responds.',
                'request_id' => $conn->insert_id
            ]);
        } else {
            // If the insert fails, send an error response
            echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $stmt->error]);
        }
        
        // Close the statement (good practice!)
        $stmt->close();
        
    } catch (Exception $e) {
        // If any exception occurs, send an error response
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    // Exit the script (we're done here!)
    exit;
}

// Get search parameters (pretty standard form -> query mapping)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12; // Books per page
$offset = ($page - 1) * $limit;

// Build search query (use placeholders; we don't build raw strings)
$whereConditions = ["b.status = 'active'"];
$params = [];

if (!empty($search)) {
    // Add a condition for searching by title, author, ISBN, or publisher
    $whereConditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($category > 0) {
    // Add a condition for filtering by category
    $whereConditions[] = "b.category_id = ?";
    $params[] = $category;
}

// Join the conditions with AND (we want all conditions to be true)
$whereClause = implode(' AND ', $whereConditions);

// Define sort options (limited to whitelisted keys for safety)
$sortOptions = [
    'title' => 'b.title ASC',
    'author' => 'b.author ASC',
    'newest' => 'b.created_at DESC',
    'available' => 'b.available_copies DESC'
];

// Get the sort order (default to title if not specified)
$orderBy = isset($sortOptions[$sort]) ? $sortOptions[$sort] : $sortOptions['title'];

// Get the total count of books (for pagination)
$totalBooks = $db->fetchColumn("\n    SELECT COUNT(*) \n    FROM books b \n    LEFT JOIN categories c ON b.category_id = c.category_id \n    WHERE {$whereClause}\n", $params) ?? 0;

// Calculate the total number of pages
$totalPages = ceil($totalBooks / $limit);

// Get the books (includes category and availability mini flag)
$books = $db->fetchAll("\n    SELECT b.*, c.category_name,\n           CASE WHEN b.available_copies > 0 THEN 'Available' ELSE 'Not Available' END as availability_status\n    FROM books b\n    LEFT JOIN categories c ON b.category_id = c.category_id\n    WHERE {$whereClause}\n    ORDER BY {$orderBy}\n    LIMIT {$limit} OFFSET {$offset}\n", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");

// Set the page title
$pageTitle = 'Search Books';

// Include the student header
include '../includes/student_header.php';
?>

<!-- Container for the search results -->
<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header (title + back to dashboard) -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-search me-2"></i>Search Books
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message (if any) -->
            <?php echo getFlashMessage(); ?>

            <!-- Search Form (filters: keyword + category + sort) -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Books</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Title, Author, ISBN, or Publisher">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" 
                                            <?php echo ($category == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="title" <?php echo ($sort == 'title') ? 'selected' : ''; ?>>Title A-Z</option>
                                <option value="author" <?php echo ($sort == 'author') ? 'selected' : ''; ?>>Author A-Z</option>
                                <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="available" <?php echo ($sort == 'available') ? 'selected' : ''; ?>>Most Available</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Results (heading + total hits) -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <?php if (!empty($search) || $category > 0): ?>
                            Search Results: <?php echo $totalBooks; ?> book(s) found
                        <?php else: ?>
                            All Books: <?php echo $totalBooks; ?> book(s)
                        <?php endif; ?>
                    </h5>
                    <?php if (!empty($search)): ?>
                        <small class="text-muted">Searching for: "<?php echo htmlspecialchars($search); ?>"</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if ($totalPages > 1): ?>
                        <small class="text-muted">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Books Grid -->
            <?php if (!empty($books)): ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 shadow-sm book-card">
                                <div class="card-img-top book-cover">
                                    <img src="<?php echo generateBookThumbnailFromData($book, 'medium'); ?>" 
                                         class="img-fluid" alt="Book Cover" 
                                         style="width: 100%; height: 200px; object-fit: cover;">
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?><br>
                                        <strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?><br>
                                        <?php if (!empty($book['category_name'])): ?>
                                            <strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?><br>
                                        <?php endif; ?>
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                Available: <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?>
                                            </small>
                                            <?php if ($book['available_copies'] > 0): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Available</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-primary w-100" 
                                                data-bs-toggle="modal" data-bs-target="#bookModal"
                                                onclick="showBookDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Search results pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Results (empty state) -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No books found</h4>
                    <p class="text-muted">
                        <?php if (!empty($search) || $category > 0): ?>
                            Try adjusting your search criteria or browse all books.
                        <?php else: ?>
                            No books are currently available in the library.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || $category > 0): ?>
                        <a href="search_books.php" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i>View All Books
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- Book Details Modal (shows details + action button to request) -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookModalLabel">Book Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div id="modalBookNameDisplay" class="book-name-display d-flex align-items-center justify-content-center text-center">
                            <h3 id="modalBookNameTitle" class="book-title-display"></h3>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 id="modalBookTitle"></h4>
                        <p class="text-muted mb-3" id="modalBookAuthor"></p>
                        
                        <table class="table table-sm">
                            <tr>
                                <th>ISBN:</th>
                                <td id="modalBookISBN"></td>
                            </tr>
                            <tr>
                                <th>Publisher:</th>
                                <td id="modalBookPublisher"></td>
                            </tr>
                            <tr>
                                <th>Publication Year:</th>
                                <td id="modalBookYear"></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td id="modalBookCategory"></td>
                            </tr>
                            <tr>
                                <th>Pages:</th>
                                <td id="modalBookPages"></td>
                            </tr>
                            <tr>
                                <th>Language:</th>
                                <td id="modalBookLanguage"></td>
                            </tr>
                            <tr>
                                <th>Availability:</th>
                                <td id="modalBookAvailability"></td>
                            </tr>
                        </table>
                        
                        <div id="modalBookDescription"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div id="modalBookActions"></div>
            </div>
        </div>
    </div>
</div>

<!-- Book Request Modal (form to submit request to librarian) -->
<div class="modal fade" id="requestBookModal" tabindex="-1" aria-labelledby="requestBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestBookModalLabel">
                    <i class="fas fa-book-reader me-2"></i>Request Book
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="requestBookForm">
                    <input type="hidden" id="requestBookId" name="book_id">
                    
                    <div class="mb-3">
                        <h6 id="requestBookTitle" class="text-primary"></h6>
                        <p class="text-muted mb-3" id="requestBookAuthor"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requestPriority" class="form-label">Priority Level</label>
                        <select class="form-select" id="requestPriority" name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <div class="form-text">Select priority based on your need</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requestDuration" class="form-label">Requested Duration (days)</label>
                        <select class="form-select" id="requestDuration" name="duration">
                            <option value="7">7 days</option>
                            <option value="14" selected>14 days (Standard)</option>
                            <option value="21">21 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requestNotes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="requestNotes" name="notes" rows="3" 
                                placeholder="Any specific requirements or notes for the librarian..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitBookRequest">
                    <i class="fas fa-paper-plane me-1"></i>Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Button styling tweaks (looks a bit more modern) */
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

/* Enhanced book cards */
.book-card {
    transition: all 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.book-cover-placeholder {
    height: 200px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
}

/* Enhanced form styling */
.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>

<?php include '../includes/student_footer.php'; ?>

<style>
.book-card {
    transition: transform 0.2s;
}

.book-card:hover {
    transform: translateY(-5px);
}

.book-cover {
    height: 200px;
    object-fit: cover;
}

.book-cover-placeholder {
    height: 200px;
    background-color: #f8f9fa;
}

.book-name-display {
    height: 300px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    border: 3px solid #f8f9fa;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.book-title-display {
    color: white;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    word-wrap: break-word;
    line-height: 1.2;
    margin: 0;
    padding: 20px;
}
</style>

<script>
// show details in the modal (just populating fields, nothing fancy)
function showBookDetails(book) {
    // Set book name in the display area
    document.getElementById('modalBookNameTitle').textContent = book.title;
    
    // Set book details
    document.getElementById('modalBookTitle').textContent = book.title;
    document.getElementById('modalBookAuthor').textContent = 'by ' + book.author;
    document.getElementById('modalBookISBN').textContent = book.isbn;
    document.getElementById('modalBookPublisher').textContent = book.publisher;
    document.getElementById('modalBookYear').textContent = book.publication_year || 'N/A';
    document.getElementById('modalBookCategory').textContent = book.category_name || 'Uncategorized';
    document.getElementById('modalBookPages').textContent = book.pages || 'N/A';
    document.getElementById('modalBookLanguage').textContent = book.language || 'N/A';
    
    // Set availability
    const availabilityElement = document.getElementById('modalBookAvailability');
    if (book.available_copies > 0) {
        availabilityElement.innerHTML = '<span class="badge bg-success">Available (' + book.available_copies + '/' + book.total_copies + ')</span>';
    } else {
        availabilityElement.innerHTML = '<span class="badge bg-danger">Not Available</span>';
    }
    
    // Set description
    const descriptionElement = document.getElementById('modalBookDescription');
    if (book.description) {
        descriptionElement.innerHTML = '<h6>Description:</h6><p>' + book.description + '</p>';
    } else {
        descriptionElement.innerHTML = '';
    }
    
    // Set actions (enable request if copies available)
    const actionsElement = document.getElementById('modalBookActions');
    if (book.available_copies > 0) {
        actionsElement.innerHTML = '<button type="button" class="btn btn-primary" onclick="openRequestModal(' + book.book_id + ', \'' + book.title.replace(/'/g, '&apos;') + '\', \'' + book.author.replace(/'/g, '&apos;') + '\')">' +
            '<i class="fas fa-book-reader me-1"></i>Request This Book' +
            '</button>';
    } else {
        actionsElement.innerHTML = '<span class="text-muted"><i class="fas fa-info-circle me-1"></i>This book is currently not available</span>';
    }
}

// Auto-submit form on category/sort change (small UX sugar)
document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});

// Focus search input on page load (I like when the cursor is ready to type 😄)
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search').focus();
});

// Open request modal (prefill with selected book data)
function openRequestModal(bookId, bookTitle, bookAuthor) {
    document.getElementById('requestBookId').value = bookId;
    document.getElementById('requestBookTitle').textContent = bookTitle;
    document.getElementById('requestBookAuthor').textContent = 'by ' + bookAuthor;
    
    // Reset form
    document.getElementById('requestBookForm').reset();
    document.getElementById('requestBookId').value = bookId; // Set again after reset
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('requestBookModal'));
    modal.show();
}

// Handle book request submission (POST back to this same page)
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('submitBookRequest').addEventListener('click', function() {
        const form = document.getElementById('requestBookForm');
        const formData = new FormData(form);
        formData.append('action', 'request_book');
        
        // Disable submit button
        const submitBtn = this;
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('✅ Book request submitted successfully! You will be notified when the librarian responds.');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('requestBookModal'));
                modal.hide();
                
                // Optionally refresh or update UI
                // You could add a notification badge or update the button text
            } else {
                alert('❌ Error: ' + (data.message || 'Failed to submit request. Please try again.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred while submitting your request. Please try again.');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>
