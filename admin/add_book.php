<?php
/**
 * Add New Book - Library Management System
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

// Require admin access
requireAdmin();

$db = Database::getInstance();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input data
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $isbn = sanitizeInput($_POST['isbn']);
        $publisher = sanitizeInput($_POST['publisher']);
        $publication_year = (int)$_POST['publication_year'];
        $category_id = (int)$_POST['category_id'];
        $edition = sanitizeInput($_POST['edition']);
        $pages = (int)$_POST['pages'];
        $language = sanitizeInput($_POST['language']);
        $total_copies = (int)$_POST['total_copies'];
        $location = sanitizeInput($_POST['location']);
        $description = sanitizeInput($_POST['description']);
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Book title is required.';
        }
        
        if (empty($author)) {
            $errors[] = 'Author name is required.';
        }
        
        if (!empty($isbn) && !isValidISBN($isbn)) {
            $errors[] = 'Please enter a valid ISBN format.';
        }
        
        if ($total_copies < 1) {
            $errors[] = 'Total copies must be at least 1.';
        }
        
        if ($publication_year > 0 && ($publication_year < 1000 || $publication_year > date('Y'))) {
            $errors[] = 'Please enter a valid publication year.';
        }
        
        // Check if ISBN already exists
        if (!empty($isbn)) {
            $existingBook = $db->fetchOne("SELECT book_id FROM books WHERE isbn = ?", [$isbn]);
            if ($existingBook) {
                $errors[] = 'A book with this ISBN already exists.';
            }
        }
        
        // Handle file upload
        $bookImage = '';
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['book_image']);
            if ($uploadResult['success']) {
                $bookImage = $uploadResult['filename'];
            } else {
                $errors[] = 'Error uploading book image: ' . $uploadResult['message'];
            }
        }
        
        // If no errors, insert the book
        if (empty($errors)) {
            try {
                $bookData = [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $isbn ?: null,
                    'publisher' => $publisher ?: null,
                    'publication_year' => $publication_year ?: null,
                    'category_id' => $category_id ?: null,
                    'edition' => $edition ?: null,
                    'pages' => $pages ?: null,
                    'language' => $language ?: 'English',
                    'total_copies' => $total_copies,
                    'available_copies' => $total_copies,
                    'location' => $location ?: null,
                    'description' => $description ?: null,
                    'book_image' => $bookImage ?: null,
                    'status' => 'active'
                ];
                
                $bookId = $db->insert('books', $bookData);
                
                if ($bookId) {
                    logSystemActivity('BOOK_ADD', "Added new book: $title by $author");
                    redirectWithMessage('books.php', 'Book added successfully!', 'success');
                } else {
                    $errors[] = 'Failed to add book. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Error adding book: " . $e->getMessage());
                $errors[] = 'An error occurred while adding the book.';
            }
        }
    }
}

// Get categories for dropdown
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");

$pageTitle = 'Add New Book';
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
                    <i class="fas fa-plus-circle me-2"></i>Add New Book
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="books.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Books
                    </a>
                </div>
            </div>

            <!-- Display errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Add Book Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i>Book Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" id="isbn" name="isbn" 
                                               value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>" 
                                               placeholder="978-XXXXXXXXXX">
                                        <div class="form-text">Optional: 10 or 13 digit ISBN</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="author" name="author" 
                                               value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="publisher" class="form-label">Publisher</label>
                                        <input type="text" class="form-control" id="publisher" name="publisher" 
                                               value="<?php echo isset($_POST['publisher']) ? htmlspecialchars($_POST['publisher']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="0">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="publication_year" class="form-label">Publication Year</label>
                                        <input type="number" class="form-control" id="publication_year" name="publication_year" 
                                               value="<?php echo isset($_POST['publication_year']) ? $_POST['publication_year'] : ''; ?>" 
                                               min="1000" max="<?php echo date('Y'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="edition" class="form-label">Edition</label>
                                        <input type="text" class="form-control" id="edition" name="edition" 
                                               value="<?php echo isset($_POST['edition']) ? htmlspecialchars($_POST['edition']) : ''; ?>" 
                                               placeholder="e.g., 1st, 2nd, Latest">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="language" class="form-label">Language</label>
                                        <select class="form-select" id="language" name="language">
                                            <option value="English" <?php echo (isset($_POST['language']) && $_POST['language'] == 'English') ? 'selected' : ''; ?>>English</option>
                                            <option value="Hindi" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                            <option value="Kannada" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Kannada') ? 'selected' : ''; ?>>Kannada</option>
                                            <option value="Tamil" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Tamil') ? 'selected' : ''; ?>>Tamil</option>
                                            <option value="Telugu" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Telugu') ? 'selected' : ''; ?>>Telugu</option>
                                            <option value="Other" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="pages" class="form-label">Number of Pages</label>
                                        <input type="number" class="form-control" id="pages" name="pages" 
                                               value="<?php echo isset($_POST['pages']) ? $_POST['pages'] : ''; ?>" 
                                               min="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="total_copies" class="form-label">Total Copies <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="total_copies" name="total_copies" 
                                               value="<?php echo isset($_POST['total_copies']) ? $_POST['total_copies'] : '1'; ?>" 
                                               min="1" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location" class="form-label">Shelf Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                           placeholder="e.g., A-001, CS-B-002">
                                    <div class="form-text">Physical location of the book in the library</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Brief description of the book content..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="book_image" class="form-label">Book Cover Image</label>
                                    <input type="file" class="form-control" id="book_image" name="book_image" 
                                           accept="image/*">
                                    <div class="form-text">Optional: Upload book cover image (JPG, PNG, GIF - Max 5MB)</div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="books.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Add Book
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Help Panel -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Quick Help
                            </h6>
                        </div>
                        <div class="card-body">
                            <h6>Required Fields</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Book Title</li>
                                <li><i class="fas fa-check text-success me-2"></i>Author Name</li>
                                <li><i class="fas fa-check text-success me-2"></i>Total Copies</li>
                            </ul>
                            
                            <h6 class="mt-3">Tips</h6>
                            <ul class="small">
                                <li>Use proper ISBN format (with or without hyphens)</li>
                                <li>Select appropriate category for better organization</li>
                                <li>Include shelf location for easy finding</li>
                                <li>Upload clear book cover image if available</li>
                            </ul>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Note:</strong> Available copies will be set equal to total copies initially.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="categories.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-tags me-1"></i>Manage Categories
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateISBN()">
                                    <i class="fas fa-barcode me-1"></i>Generate Sample ISBN
                                </button>
                                <a href="books.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-list me-1"></i>View All Books
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
// Generate sample ISBN
function generateISBN() {
    const isbn = '<?php echo generateISBN(); ?>';
    document.getElementById('isbn').value = isbn;
}

// Auto-generate shelf location based on category and title
document.getElementById('category_id').addEventListener('change', function() {
    const categorySelect = this;
    const locationInput = document.getElementById('location');
    const titleInput = document.getElementById('title');
    
    if (categorySelect.value && titleInput.value && !locationInput.value) {
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text;
        const categoryCode = categoryText.substring(0, 2).toUpperCase();
        const randomNum = Math.floor(Math.random() * 900) + 100;
        locationInput.value = categoryCode + '-' + randomNum;
    }
});

// File upload preview
document.getElementById('book_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size
        if (file.size > <?php echo MAX_FILE_SIZE; ?>) {
            alert('File size exceeds 5MB limit. Please choose a smaller image.');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF).');
            this.value = '';
            return;
        }
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const author = document.getElementById('author').value.trim();
    const totalCopies = parseInt(document.getElementById('total_copies').value);
    
    if (!title) {
        alert('Please enter the book title.');
        e.preventDefault();
        return;
    }
    
    if (!author) {
        alert('Please enter the author name.');
        e.preventDefault();
        return;
    }
    
    if (totalCopies < 1) {
        alert('Total copies must be at least 1.');
        e.preventDefault();
        return;
    }
});
</script>
