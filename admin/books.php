<?php
/**
 * Books Management - Library Management System
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
require_once '../includes/thumbnail_generator.php';

// Require admin access
requireAdmin();

$db = Database::getInstance();

// Handle search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $recordsPerPage;

// Build search query
$whereClause = "WHERE b.status = 'active'";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($category > 0) {
    $whereClause .= " AND b.category_id = ?";
    $params[] = $category;
}

// Get total count
$countSql = "SELECT COUNT(*) FROM books b $whereClause";
$totalRecords = $db->fetchColumn($countSql, $params);

// Get books with pagination
$sql = "SELECT b.*, c.category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.category_id 
        $whereClause 
        ORDER BY b.title ASC 
        LIMIT $recordsPerPage OFFSET $offset";

$books = $db->fetchAll($sql, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");

$pageTitle = 'Manage Books';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-book me-2"></i>Manage Books
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add_book.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Add New Book
                        </a>
                        <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-tags me-1"></i>Manage Categories
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Books</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by title, author, or ISBN">
                            </div>
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
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="books.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Books Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Books List
                        <span class="badge bg-primary ms-2"><?php echo number_format($totalRecords); ?> total</span>
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="exportBooks()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($books)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Book Details</th>
                                        <th>Category</th>
                                        <th>Copies</th>
                                        <th>Availability</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex">
                                                    <div class="book-cover me-3">
                                                        <img src="<?php echo generateBookThumbnailFromData($book, 'small'); ?>" 
                                                             alt="Book Cover" class="img-thumbnail" 
                                                             style="width: 50px; height: 60px; object-fit: cover;">
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                        <p class="mb-1 text-muted">by <?php echo htmlspecialchars($book['author']); ?></p>
                                                        <small class="text-muted">
                                                            ISBN: <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?> | 
                                                            Published: <?php echo htmlspecialchars($book['publication_year'] ?? 'N/A'); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="fw-bold text-primary"><?php echo $book['available_copies']; ?></div>
                                                    <small class="text-muted">of <?php echo $book['total_copies']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo getBookStatusBadge($book['available_copies'], $book['total_copies']); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_book.php?id=<?php echo $book['book_id']; ?>" 
                                                       class="btn btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteBook(<?php echo $book['book_id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No books found</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || $category > 0): ?>
                                    Try adjusting your search criteria or <a href="books.php">view all books</a>.
                                <?php else: ?>
                                    Start by <a href="add_book.php">adding your first book</a> to the library.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($totalRecords > $recordsPerPage): ?>
                    <div class="card-footer">
                        <?php 
                        $baseUrl = 'books.php';
                        $queryParams = [];
                        if (!empty($search)) $queryParams[] = 'search=' . urlencode($search);
                        if ($category > 0) $queryParams[] = 'category=' . $category;
                        if (!empty($queryParams)) $baseUrl .= '?' . implode('&', $queryParams) . '&';
                        else $baseUrl .= '?';
                        
                        echo generatePagination($page, $totalRecords, $recordsPerPage, $baseUrl);
                        ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this book? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> You can only delete books that are not currently issued to any student.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-2"></i>Delete Book
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
let bookToDelete = null;

function deleteBook(bookId) {
    bookToDelete = bookId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (bookToDelete) {
        const restoreButton = showLoading(this);
        
        fetch('ajax/delete_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                book_id: bookToDelete,
                csrf_token: '<?php echo csrfToken(); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            restoreButton();
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            restoreButton();
            alert('An error occurred while deleting the book.');
            console.error('Error:', error);
        });
    }
});

function exportBooks() {
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.append('export', '1');
    window.location.href = 'export_books.php?' + searchParams.toString();
}

// Auto-submit search form on category change
document.getElementById('category').addEventListener('change', function() {
    this.form.submit();
});
</script>
