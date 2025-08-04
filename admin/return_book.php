<?php
/**
 * Return Book - Library Management System
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
        $issue_id = (int)$_POST['issue_id'];
        $return_condition = sanitizeInput($_POST['return_condition']);
        $notes = sanitizeInput($_POST['notes']);
        $waive_fine = isset($_POST['waive_fine']) ? 1 : 0;
        
        // Validation
        if ($issue_id <= 0) {
            $errors[] = 'Please select a valid book issue.';
        }
        
        // Get issue details
        if (empty($errors)) {
            $issue = $db->fetchOne("
                SELECT bi.*, b.title, b.author, u.full_name, u.registration_number
                FROM book_issues bi
                JOIN books b ON bi.book_id = b.book_id
                JOIN users u ON bi.user_id = u.user_id
                WHERE bi.issue_id = ? AND bi.status IN ('issued', 'overdue')
            ", [$issue_id]);
            
            if (!$issue) {
                $errors[] = 'Selected book issue not found or already returned.';
            }
        }
        
        // If no errors, process return
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                $returnDate = date('Y-m-d');
                $returnTime = date('Y-m-d H:i:s');
                
                // Calculate fine if overdue
                $fineAmount = 0;
                $daysOverdue = 0;
                
                if (strtotime($returnDate) > strtotime($issue['due_date'])) {
                    $daysOverdue = daysBetween($issue['due_date'], $returnDate);
                    $finePerDay = getSystemSetting('fine_per_day', FINE_PER_DAY);
                    $fineAmount = $daysOverdue * $finePerDay;
                    
                    if ($waive_fine) {
                        $fineAmount = 0;
                    }
                }
                
                // Update book issue record
                $updateData = [
                    'return_date' => $returnDate,
                    'status' => 'returned',
                    'returned_by' => $_SESSION['user_id'],
                    'notes' => $notes ?: null
                ];
                
                $db->update('book_issues', $updateData, 'issue_id = ?', [$issue_id]);
                
                // Update book availability
                $db->query("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?", [$issue['book_id']]);
                
                // Create fine record if applicable
                if ($fineAmount > 0) {
                    $fineData = [
                        'issue_id' => $issue_id,
                        'user_id' => $issue['user_id'],
                        'fine_amount' => $fineAmount,
                        'fine_per_day' => $finePerDay,
                        'days_overdue' => $daysOverdue,
                        'status' => 'pending',
                        'notes' => "Fine for returning '{$issue['title']}' $daysOverdue days late"
                    ];
                    
                    $db->insert('fines', $fineData);
                }
                
                $db->commit();
                
                $message = "Book '{$issue['title']}' returned successfully by {$issue['full_name']}";
                if ($fineAmount > 0) {
                    $message .= ". Fine of ₹$fineAmount applied for $daysOverdue days overdue.";
                } elseif ($daysOverdue > 0 && $waive_fine) {
                    $message .= ". Fine waived by admin.";
                }
                
                logSystemActivity('BOOK_RETURN', $message);
                redirectWithMessage('issues.php', $message, 'success');
                
            } catch (Exception $e) {
                $db->rollback();
                error_log("Error returning book: " . $e->getMessage());
                $errors[] = 'An error occurred while processing the return.';
            }
        }
    }
}

// Get active issues for quick selection
$activeIssues = $db->fetchAll("
    SELECT bi.*, b.title, b.author, u.full_name, u.registration_number,
           DATEDIFF(CURDATE(), bi.due_date) as days_overdue
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.book_id
    JOIN users u ON bi.user_id = u.user_id
    WHERE bi.status IN ('issued', 'overdue')
    ORDER BY bi.due_date ASC
    LIMIT 20
");

$pageTitle = 'Return Book';
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
                    <i class="fas fa-undo me-2"></i>Return Book
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="issues.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>View All Issues
                        </a>
                        <a href="issue_book.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-hand-holding me-1"></i>Issue Book
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
                <!-- Return Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-undo me-2"></i>Process Book Return
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="returnForm">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                
                                <!-- Issue Selection -->
                                <div class="mb-4">
                                    <label for="issue_search" class="form-label">Search Book Issue <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="issue_search" 
                                               placeholder="Search by book title, user name, or registration number..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearIssueSelection()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="issue_id" name="issue_id" required>
                                    <div id="issue_results" class="mt-2"></div>
                                    <div id="selected_issue" class="mt-2" style="display: none;">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6 class="card-title">Selected Issue Details</h6>
                                                <div id="issue_details"></div>
                                                <div id="fine_calculation" class="mt-3"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Return Details -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="return_condition" class="form-label">Book Condition</label>
                                        <select class="form-select" id="return_condition" name="return_condition">
                                            <option value="good">Good Condition</option>
                                            <option value="fair">Fair Condition</option>
                                            <option value="poor">Poor Condition</option>
                                            <option value="damaged">Damaged</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="return_date_display" class="form-label">Return Date</label>
                                        <input type="text" class="form-control" id="return_date_display" 
                                               value="<?php echo formatDate(date('Y-m-d')); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Return Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any notes about the book condition or return process..."></textarea>
                                </div>
                                
                                <!-- Fine Waiver Option -->
                                <div class="mb-3" id="fine_waiver_section" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="waive_fine" name="waive_fine">
                                        <label class="form-check-label" for="waive_fine">
                                            <strong>Waive Fine</strong> (Admin discretion)
                                        </label>
                                    </div>
                                    <small class="text-muted">Check this box to waive the overdue fine for this return.</small>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="issues.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success" id="returnButton" disabled>
                                        <i class="fas fa-undo me-1"></i>Process Return
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Active Issues Panel -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Active Issues
                                <span class="badge bg-warning ms-2"><?php echo count($activeIssues); ?></span>
                            </h6>
                        </div>
                        <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                            <?php if (!empty($activeIssues)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($activeIssues as $issue): ?>
                                        <?php 
                                        $isOverdue = $issue['days_overdue'] > 0;
                                        $statusClass = $isOverdue ? 'list-group-item-danger' : 'list-group-item-light';
                                        ?>
                                        <a href="#" class="list-group-item list-group-item-action <?php echo $statusClass; ?>" 
                                           onclick="selectIssue(<?php echo htmlspecialchars(json_encode($issue)); ?>)">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($issue['title']); ?></h6>
                                                <?php if ($isOverdue): ?>
                                                    <small class="text-danger fw-bold"><?php echo $issue['days_overdue']; ?> days overdue</small>
                                                <?php else: ?>
                                                    <small class="text-success">On time</small>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1">
                                                <strong><?php echo htmlspecialchars($issue['full_name']); ?></strong>
                                                <small class="text-muted">(<?php echo htmlspecialchars($issue['registration_number']); ?>)</small>
                                            </p>
                                            <small class="text-muted">
                                                Due: <?php echo formatDate($issue['due_date']); ?> | 
                                                Issued: <?php echo formatDate($issue['issue_date']); ?>
                                            </small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h6 class="text-muted">No Active Issues</h6>
                                    <p class="text-muted small">All books have been returned!</p>
                                </div>
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
let issueSearchTimeout;
let selectedIssueData = null;

// Issue search functionality
document.getElementById('issue_search').addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(issueSearchTimeout);
    
    if (query.length < 2) {
        document.getElementById('issue_results').innerHTML = '';
        return;
    }
    
    issueSearchTimeout = setTimeout(() => {
        searchIssues(query);
    }, 300);
});

function searchIssues(query) {
    fetch('ajax/search_issues.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            query: query,
            status: 'active',
            csrf_token: '<?php echo csrfToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        displayIssueResults(data.issues || []);
    })
    .catch(error => {
        console.error('Error searching issues:', error);
    });
}

function displayIssueResults(issues) {
    const resultsDiv = document.getElementById('issue_results');
    
    if (issues.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">No active issues found.</div>';
        return;
    }
    
    let html = '<div class="list-group">';
    issues.forEach(issue => {
        const isOverdue = issue.days_overdue > 0;
        const statusClass = isOverdue ? 'list-group-item-danger' : '';
        
        html += `
            <a href="#" class="list-group-item list-group-item-action ${statusClass}" 
               onclick="selectIssue(${JSON.stringify(issue).replace(/"/g, '&quot;')})">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${issue.title}</h6>
                    ${isOverdue ? `<small class="text-danger fw-bold">${issue.days_overdue} days overdue</small>` : '<small class="text-success">On time</small>'}
                </div>
                <p class="mb-1"><strong>${issue.full_name}</strong> <small class="text-muted">(${issue.registration_number})</small></p>
                <small class="text-muted">Due: ${issue.due_date} | Issued: ${issue.issue_date}</small>
            </a>
        `;
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

function selectIssue(issue) {
    selectedIssueData = issue;
    
    document.getElementById('issue_id').value = issue.issue_id;
    document.getElementById('issue_search').value = `${issue.title} - ${issue.full_name}`;
    
    // Display issue details
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <strong>Book:</strong> ${issue.title}<br>
                <strong>Author:</strong> ${issue.author}<br>
                <strong>User:</strong> ${issue.full_name}
            </div>
            <div class="col-md-6">
                <strong>Issue Date:</strong> ${issue.issue_date}<br>
                <strong>Due Date:</strong> ${issue.due_date}<br>
                <strong>Status:</strong> <span class="badge bg-${issue.days_overdue > 0 ? 'danger' : 'success'}">${issue.days_overdue > 0 ? 'Overdue' : 'On Time'}</span>
            </div>
        </div>
    `;
    
    document.getElementById('issue_details').innerHTML = detailsHtml;
    
    // Calculate and display fine
    calculateFine(issue);
    
    document.getElementById('selected_issue').style.display = 'block';
    document.getElementById('issue_results').innerHTML = '';
    document.getElementById('returnButton').disabled = false;
}

function calculateFine(issue) {
    const fineCalculationDiv = document.getElementById('fine_calculation');
    const fineWaiverSection = document.getElementById('fine_waiver_section');
    
    if (issue.days_overdue > 0) {
        const finePerDay = <?php echo getSystemSetting('fine_per_day', FINE_PER_DAY); ?>;
        const totalFine = issue.days_overdue * finePerDay;
        
        fineCalculationDiv.innerHTML = `
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Overdue Fine Calculation</h6>
                <p class="mb-1"><strong>Days Overdue:</strong> ${issue.days_overdue}</p>
                <p class="mb-1"><strong>Fine per Day:</strong> ₹${finePerDay}</p>
                <p class="mb-0"><strong>Total Fine:</strong> ₹${totalFine.toFixed(2)}</p>
            </div>
        `;
        
        fineWaiverSection.style.display = 'block';
    } else {
        fineCalculationDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>No fine applicable - book returned on time!
            </div>
        `;
        
        fineWaiverSection.style.display = 'none';
    }
}

function clearIssueSelection() {
    selectedIssueData = null;
    document.getElementById('issue_id').value = '';
    document.getElementById('issue_search').value = '';
    document.getElementById('selected_issue').style.display = 'none';
    document.getElementById('issue_results').innerHTML = '';
    document.getElementById('returnButton').disabled = true;
    document.getElementById('fine_waiver_section').style.display = 'none';
}

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const issueId = document.getElementById('issue_id').value;
    
    if (!issueId) {
        alert('Please select a book issue to process return.');
        e.preventDefault();
        return;
    }
    
    if (selectedIssueData && selectedIssueData.days_overdue > 0) {
        const waiveFine = document.getElementById('waive_fine').checked;
        const confirmMessage = waiveFine 
            ? 'Are you sure you want to process this return and waive the fine?'
            : `This return will incur a fine of ₹${(selectedIssueData.days_overdue * <?php echo getSystemSetting('fine_per_day', FINE_PER_DAY); ?>).toFixed(2)}. Continue?`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return;
        }
    }
});
</script>
