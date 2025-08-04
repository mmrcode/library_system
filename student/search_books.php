<?php
/**
 * Student Search Books - Library Management System
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

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12; // Books per page
$offset = ($page - 1) * $limit;

// Build search query
$whereConditions = ["b.status = 'active'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($category > 0) {
    $whereConditions[] = "b.category_id = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $whereConditions);

// Sort options
$sortOptions = [
    'title' => 'b.title ASC',
    'author' => 'b.author ASC',
    'newest' => 'b.created_at DESC',
    'available' => 'b.available_copies DESC'
];

$orderBy = isset($sortOptions[$sort]) ? $sortOptions[$sort] : $sortOptions['title'];

// Get total count
$totalBooks = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.category_id 
    WHERE {$whereClause}
", $params) ?? 0;

$totalPages = ceil($totalBooks / $limit);

// Get books
$books = $db->fetchAll("
    SELECT b.*, c.category_name,
           CASE WHEN b.available_copies > 0 THEN 'Available' ELSE 'Not Available' END as availability_status
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");

$pageTitle = 'Search Books';
include '../includes/student_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4">
            <!-- Page Header -->
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

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Search Form -->
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

            <!-- Search Results -->
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
                <!-- No Results -->
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

<!-- Book Details Modal -->
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

<style>
/* Enhanced button styling for search page */
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
    
    // Set actions
    const actionsElement = document.getElementById('modalBookActions');
    if (book.available_copies > 0) {
        actionsElement.innerHTML = '<span class="text-success"><i class="fas fa-info-circle me-1"></i>Contact librarian to issue this book</span>';
    } else {
        actionsElement.innerHTML = '<span class="text-muted"><i class="fas fa-info-circle me-1"></i>This book is currently not available</span>';
    }
}

// Auto-submit form on category/sort change
document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});

// Focus search input on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search').focus();
});
</script>
