<?php
/**
 * Categories Management - Library Management System
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
    } else {
        if (isset($_POST['add_category'])) {
            $categoryName = sanitizeInput($_POST['category_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($categoryName)) {
                setFlashMessage('Category name is required.', 'error');
            } else {
                // Check if category already exists
                $existing = $db->fetchRow("SELECT category_id FROM categories WHERE category_name = ?", [$categoryName]);
                if ($existing) {
                    setFlashMessage('Category already exists.', 'error');
                } else {
                    $result = $db->insert('categories', [
                        'category_name' => $categoryName,
                        'description' => $description
                    ]);
                    
                    if ($result) {
                        logActivity(getCurrentUser()['user_id'], 'category_add', "Added category: $categoryName");
                        setFlashMessage('Category added successfully!', 'success');
                    } else {
                        setFlashMessage('Failed to add category. Please try again.', 'error');
                    }
                }
            }
        } elseif (isset($_POST['update_category'])) {
            $categoryId = (int)$_POST['category_id'];
            $categoryName = sanitizeInput($_POST['category_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($categoryName)) {
                setFlashMessage('Category name is required.', 'error');
            } else {
                $result = $db->update('categories', [
                    'category_name' => $categoryName,
                    'description' => $description
                ], ['category_id' => $categoryId]);
                
                if ($result) {
                    logActivity(getCurrentUser()['user_id'], 'category_update', "Updated category: $categoryName");
                    setFlashMessage('Category updated successfully!', 'success');
                } else {
                    setFlashMessage('Failed to update category. Please try again.', 'error');
                }
            }
        } elseif (isset($_POST['delete_category'])) {
            $categoryId = (int)$_POST['category_id'];
            
            // Check if category has books
            $bookCount = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE category_id = ?", [$categoryId]);
            if ($bookCount > 0) {
                setFlashMessage("Cannot delete category. It has $bookCount book(s) assigned to it.", 'error');
            } else {
                $category = $db->fetchRow("SELECT category_name FROM categories WHERE category_id = ?", [$categoryId]);
                $result = $db->delete('categories', ['category_id' => $categoryId]);
                
                if ($result) {
                    logActivity(getCurrentUser()['user_id'], 'category_delete', "Deleted category: " . $category['category_name']);
                    setFlashMessage('Category deleted successfully!', 'success');
                } else {
                    setFlashMessage('Failed to delete category. Please try again.', 'error');
                }
            }
        }
    }
}

// Get categories with book counts
$categories = $db->fetchAll("
    SELECT c.*, 
           COUNT(b.book_id) as book_count,
           COUNT(CASE WHEN b.status = 'active' THEN 1 END) as active_books
    FROM categories c
    LEFT JOIN books b ON c.category_id = b.category_id
    GROUP BY c.category_id, c.category_name, c.description, c.created_at
    ORDER BY c.category_name
");

$pageTitle = 'Categories Management';
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-tags me-2"></i>Categories Management
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-1"></i>Add Category
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Message -->
            <?php echo getFlashMessage(); ?>

            <!-- Categories Table -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>All Categories
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Books</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($category['description'] ?? 'No description'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $category['book_count']; ?> total</span>
                                                <span class="badge bg-success"><?php echo $category['active_books']; ?> active</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                            <td><?php echo formatDate($category['created_at']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($category['book_count'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No categories found</h5>
                            <p class="text-muted">Add your first category to organize books.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"?
                    </div>
                    
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.category_id;
    document.getElementById('edit_category_name').value = category.category_name;
    document.getElementById('edit_description').value = category.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function deleteCategory(categoryId, categoryName) {
    document.getElementById('delete_category_id').value = categoryId;
    document.getElementById('delete_category_name').textContent = categoryName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}
</script>
