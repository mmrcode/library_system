<?php
/**
 * Issue Book - Library Management System
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
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $book_id = (int)$_POST['book_id'];
        $user_id = (int)$_POST['user_id'];
        $issue_days = (int)$_POST['issue_days'];
        $notes = sanitizeInput($_POST['notes']);
        
        // Validation
        if ($book_id <= 0) {
            $errors[] = 'Please select a valid book.';
        }
        
        if ($user_id <= 0) {
            $errors[] = 'Please select a valid user.';
        }
        
        if ($issue_days <= 0 || $issue_days > 90) {
            $errors[] = 'Issue days must be between 1 and 90.';
        }
        
        // Check if book exists and is available
        if (empty($errors)) {
            $book = $db->fetchOne("SELECT * FROM books WHERE book_id = ? AND status = 'active'", [$book_id]);
            if (!$book) {
                $errors[] = 'Selected book not found or inactive.';
            } elseif ($book['available_copies'] <= 0) {
                $errors[] = 'This book is currently not available for issue.';
            }
        }
        
        // Check if user exists and is active
        if (empty($errors)) {
            $user = $db->fetchOne("SELECT * FROM users WHERE user_id = ? AND status = 'active'", [$user_id]);
            if (!$user) {
                $errors[] = 'Selected user not found or inactive.';
            }
        }
        
        // Check if user can issue more books
        if (empty($errors) && !canIssueMoreBooks($user_id)) {
            $maxBooks = getSystemSetting('max_books_per_user', MAX_BOOKS_PER_USER);
            $errors[] = "User has reached the maximum limit of $maxBooks books.";
        }
        
        // Check if user has overdue books
        if (empty($errors)) {
            $overdueCount = getOverdueBooksCount($user_id);
            if ($overdueCount > 0) {
                $errors[] = "User has $overdueCount overdue books. Please return them before issuing new books.";
            }
        }
        
        // Check if user already has this book
        if (empty($errors)) {
            $existingIssue = $db->fetchOne(
                "SELECT issue_id FROM book_issues WHERE book_id = ? AND user_id = ? AND status = 'issued'", 
                [$book_id, $user_id]
            );
            if ($existingIssue) {
                $errors[] = 'User already has this book issued.';
            }
        }
        
        // If no errors, issue the book
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                $issueDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime("+$issue_days days"));
                
                $issueData = [
                    'book_id' => $book_id,
                    'user_id' => $user_id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => 'issued',
                    'issued_by' => $_SESSION['user_id'],
                    'notes' => $notes ?: null
                ];
                
                $issueId = $db->insert('book_issues', $issueData);
                
                if ($issueId) {
                    // Update book availability (handled by trigger, but let's be explicit)
                    $db->update('books', 
                        ['available_copies' => $book['available_copies'] - 1], 
                        'book_id = ?', 
                        [$book_id]
                    );
                    
                    $db->commit();
                    
                    logSystemActivity('BOOK_ISSUE', "Issued book '{$book['title']}' to {$user['full_name']}");
                    redirectWithMessage('issues.php', 'Book issued successfully!', 'success');
                } else {
                    $db->rollback();
                    $errors[] = 'Failed to issue book. Please try again.';
                }
            } catch (Exception $e) {
                $db->rollback();
                error_log("Error issuing book: " . $e->getMessage());
                $errors[] = 'An error occurred while issuing the book.';
            }
        }
    }
}

// Get recent books for quick selection
$recentBooks = $db->fetchAll("
    SELECT b.*, c.category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.category_id 
    WHERE b.status = 'active' AND b.available_copies > 0 
    ORDER BY b.created_at DESC 
    LIMIT 10
");

// Get recent users for quick selection
$recentUsers = $db->fetchAll("
    SELECT * FROM users 
    WHERE user_type IN ('student', 'faculty') AND status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 10
");

$pageTitle = 'Issue Book';
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
                    <i class="fas fa-hand-holding me-2"></i>Issue Book
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="issues.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>View All Issues
                        </a>
                        <a href="return_book.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-undo me-1"></i>Return Book
                        </a>
                    </div>
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

            <div class="row">
                <!-- Issue Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>Issue New Book
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="issueForm">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                
                                <!-- Book Selection -->
                                <div class="mb-4">
                                    <label for="book_search" class="form-label">Select Book <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-book"></i></span>
                                        <input type="text" class="form-control" id="book_search" 
                                               placeholder="Search books by title, author, or ISBN..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearBookSelection()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="book_id" name="book_id" required>
                                    <div id="book_results" class="mt-2"></div>
                                    <div id="selected_book" class="mt-2" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Selected Book:</strong> <span id="selected_book_info"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- User Selection -->
                                <div class="mb-4">
                                    <label for="user_search" class="form-label">Select User <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="user_search" 
                                               placeholder="Search users by name, username, or registration number..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearUserSelection()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="user_id" name="user_id" required>
                                    <div id="user_results" class="mt-2"></div>
                                    <div id="selected_user" class="mt-2" style="display: none;">
                                        <div class="alert alert-success">
                                            <strong>Selected User:</strong> <span id="selected_user_info"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Issue Details -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="issue_days" class="form-label">Issue Period (Days) <span class="text-danger">*</span></label>
                                        <select class="form-select" id="issue_days" name="issue_days" required>
                                            <option value="7">7 Days</option>
                                            <option value="14" selected>14 Days (Default)</option>
                                            <option value="21">21 Days</option>
                                            <option value="30">30 Days</option>
                                            <option value="60">60 Days</option>
                                            <option value="90">90 Days</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="due_date_display" class="form-label">Due Date</label>
                                        <input type="text" class="form-control" id="due_date_display" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any special notes or instructions..."></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="issues.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-hand-holding me-1"></i>Issue Book
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Selection Panel -->
                <div class="col-lg-4">
                    <!-- Recent Books -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Books
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentBooks)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentBooks as $book): ?>
                                        <a href="#" class="list-group-item list-group-item-action" 
                                           onclick="selectBook(<?php echo $book['book_id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>', '<?php echo htmlspecialchars($book['author']); ?>', <?php echo $book['available_copies']; ?>)">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo generateBookThumbnailFromData($book, 'small'); ?>" 
                                                     class="me-3 rounded" 
                                                     style="width: 40px; height: 50px; object-fit: cover;" 
                                                     alt="Book Cover">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                                        <small class="text-success"><?php echo $book['available_copies']; ?> available</small>
                                                    </div>
                                                    <p class="mb-1 text-muted">by <?php echo htmlspecialchars($book['author']); ?></p>
                                                    <small class="text-muted"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center p-3">No books available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-users me-2"></i>Recent Users
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentUsers)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentUsers as $user): ?>
                                        <a href="#" class="list-group-item list-group-item-action" 
                                           onclick="selectUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo htmlspecialchars($user['registration_number'] ?? $user['username']); ?>')">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                                <small class="text-primary"><?php echo ucfirst($user['user_type']); ?></small>
                                            </div>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($user['registration_number'] ?? $user['username']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['department'] ?? 'No department'); ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center p-3">No users found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
let bookSearchTimeout;
let userSearchTimeout;

// Book search functionality
document.getElementById('book_search').addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(bookSearchTimeout);
    
    if (query.length < 2) {
        document.getElementById('book_results').innerHTML = '';
        return;
    }
    
    bookSearchTimeout = setTimeout(() => {
        searchBooks(query);
    }, 300);
});

function searchBooks(query) {
    fetch('ajax/search_books.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            query: query,
            available_only: true,
            csrf_token: '<?php echo csrfToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        displayBookResults(data.books || []);
    })
    .catch(error => {
        console.error('Error searching books:', error);
    });
}

function displayBookResults(books) {
    const resultsDiv = document.getElementById('book_results');
    
    if (books.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">No available books found.</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    books.forEach(book => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" 
               onclick="selectBook(${book.book_id}, '${book.title}', '${book.author}', ${book.available_copies})">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${book.title}</h6>
                    <small class="text-success">${book.available_copies} available</small>
                </div>
                <p class="mb-1 text-muted">by ${book.author}</p>
                <small class="text-muted">${book.isbn || 'No ISBN'} | ${book.category_name || 'Uncategorized'}</small>
            </a>
        `;
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

function selectBook(bookId, title, author, availableCopies) {
    document.getElementById('book_id').value = bookId;
    document.getElementById('book_search').value = `${title} by ${author}`;
    document.getElementById('selected_book_info').innerHTML = `${title} by ${author} (${availableCopies} available)`;
    document.getElementById('selected_book').style.display = 'block';
    document.getElementById('book_results').innerHTML = '';
}

function clearBookSelection() {
    document.getElementById('book_id').value = '';
    document.getElementById('book_search').value = '';
    document.getElementById('selected_book').style.display = 'none';
    document.getElementById('book_results').innerHTML = '';
}

// User search functionality
document.getElementById('user_search').addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(userSearchTimeout);
    
    if (query.length < 2) {
        document.getElementById('user_results').innerHTML = '';
        return;
    }
    
    userSearchTimeout = setTimeout(() => {
        searchUsers(query);
    }, 300);
});

function searchUsers(query) {
    fetch('ajax/search_users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            query: query,
            active_only: true,
            csrf_token: '<?php echo csrfToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        displayUserResults(data.users || []);
    })
    .catch(error => {
        console.error('Error searching users:', error);
    });
}

function displayUserResults(users) {
    const resultsDiv = document.getElementById('user_results');
    
    if (users.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">No active users found.</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    users.forEach(user => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" 
               onclick="selectUser(${user.user_id}, '${user.full_name}', '${user.registration_number || user.username}')">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${user.full_name}</h6>
                    <small class="text-primary">${user.user_type}</small>
                </div>
                <p class="mb-1 text-muted">${user.registration_number || user.username}</p>
                <small class="text-muted">${user.department || 'No department'} | ${user.active_issues || 0} active issues</small>
            </a>
        `;
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

function selectUser(userId, fullName, identifier) {
    document.getElementById('user_id').value = userId;
    document.getElementById('user_search').value = `${fullName} (${identifier})`;
    document.getElementById('selected_user_info').innerHTML = `${fullName} (${identifier})`;
    document.getElementById('selected_user').style.display = 'block';
    document.getElementById('user_results').innerHTML = '';
}

function clearUserSelection() {
    document.getElementById('user_id').value = '';
    document.getElementById('user_search').value = '';
    document.getElementById('selected_user').style.display = 'none';
    document.getElementById('user_results').innerHTML = '';
}

// Calculate due date
function updateDueDate() {
    const issueDays = parseInt(document.getElementById('issue_days').value);
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + issueDays);
    
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
    };
    
    document.getElementById('due_date_display').value = dueDate.toLocaleDateString('en-US', options);
}

// Initialize due date and update on change
document.addEventListener('DOMContentLoaded', function() {
    updateDueDate();
    document.getElementById('issue_days').addEventListener('change', updateDueDate);
});

// Form validation
document.getElementById('issueForm').addEventListener('submit', function(e) {
    const bookId = document.getElementById('book_id').value;
    const userId = document.getElementById('user_id').value;
    
    if (!bookId) {
        alert('Please select a book to issue.');
        e.preventDefault();
        return;
    }
    
    if (!userId) {
        alert('Please select a user to issue the book to.');
        e.preventDefault();
        return;
    }
});
</script>
