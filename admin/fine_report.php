<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fine_functions.php';

// basic guard: only admins allowed beyond this point
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// tiny bootstrap: make sure tables exist (safe if already created)
initializeFineSystem($pdo);

// fetch dashboard stats (used for the top cards)
$stats = getFineStatistics($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Calculation Report - Admin Dashboard</title><!-- kept it simple title -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            margin: 20px;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header-section h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .header-section p {
            margin: 15px 0 0 0;
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .stat-card .value {
            font-size: 2.2em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .stat-card.outstanding { border-left: 5px solid #e74c3c; }
        .stat-card.outstanding .icon { color: #e74c3c; }
        .stat-card.outstanding .value { color: #e74c3c; }
        
        .stat-card.collected { border-left: 5px solid #27ae60; }
        .stat-card.collected .icon { color: #27ae60; }
        .stat-card.collected .value { color: #27ae60; }
        
        .stat-card.average { border-left: 5px solid #3498db; }
        .stat-card.average .icon { color: #3498db; }
        .stat-card.average .value { color: #3498db; }
        
        .stat-card.students { border-left: 5px solid #f39c12; }
        .stat-card.students .icon { color: #f39c12; }
        .stat-card.students .value { color: #f39c12; }
        
        .action-section {
            padding: 30px;
        }
        
        .btn-custom {
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 8px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 20px;
            margin: 20px 0;
        }
        
        .quick-actions {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1e7dd; color: #0f5132; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .status-waived { background: #cff4fc; color: #055160; }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book-open"></i> Library Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link" href="book_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
                <a class="nav-link active" href="fine_report.php"><i class="fas fa-calculator"></i> Fines</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <h1><i class="fas fa-calculator"></i> Fine Calculation Report</h1>
            <p>Comprehensive Fine Management & Collection Analysis</p>
            <small>Last Updated: <?php echo date('F j, Y g:i A'); ?></small>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card outstanding">
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="value">₹<?php echo number_format($stats['total_outstanding'] ?? 0, 2); ?></div>
                <div class="label">Total Outstanding</div>
                <small><?php echo $stats['total_fines_count'] ?? 0; ?> overdue items</small>
            </div>
            
            <div class="stat-card collected">
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="value">₹<?php echo number_format($stats['collected_this_month'] ?? 0, 2); ?></div>
                <div class="label">Collected This Month</div>
                <small><?php echo $stats['collected_count'] ?? 0; ?> payments received</small>
            </div>
            
            <div class="stat-card average">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <div class="value">₹<?php echo number_format($stats['average_fine'] ?? 0, 2); ?></div>
                <div class="label">Average Fine</div>
                <small>Per overdue item</small>
            </div>
            
            <div class="stat-card students">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="value"><?php echo $stats['students_with_fines'] ?? 0; ?></div>
                <div class="label">Students with Fines</div>
                <small>Active fine records</small>
            </div>
        </div>

        <!-- Action Section -->
        <div class="action-section">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-custom w-100" onclick="calculateOverdueFines()">
                            <i class="fas fa-calculator"></i> Calculate Overdue Fines
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning btn-custom w-100" onclick="sendFineReminders()">
                            <i class="fas fa-envelope"></i> Send Reminders
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success btn-custom w-100" onclick="openDetailedReport()">
                            <i class="fas fa-chart-bar"></i> Detailed Report
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info btn-custom w-100" onclick="exportFineData()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Recent Fine Records -->
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); color: white;">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Recent Fine Records</h5>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading fine records...</p>
                    </div>
                    
                    <div class="table-responsive" id="fineTableContainer" style="display: none;">
                        <table class="table table-hover" id="fineTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Book Title</th>
                                    <th>Days Overdue</th>
                                    <th>Fine Amount</th>
                                    <th>Status</th>
                                    <th>Calculated Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fineTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fine Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Fine Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="paymentFineId">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="paymentNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="processPayment()">Process Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fine Waiver Modal -->
    <div class="modal fade" id="waiverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Waive Fine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="waiverForm">
                        <input type="hidden" id="waiverFineId">
                        <div class="mb-3">
                            <label class="form-label">Reason for Waiver</label>
                            <textarea class="form-control" id="waiverReason" rows="3" required placeholder="Please provide a reason for waiving this fine..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="processWaiver()">Waive Fine</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load fine records on page load (plain JS to avoid extra deps)
        document.addEventListener('DOMContentLoaded', function() {
            loadFineRecords();
        });

        // Load fine records
        // note: API returns { success, data } – we show spinner meanwhile
        function loadFineRecords() {
            fetch('api/fine_data.php?action=report')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateFineTable(data.data);
                        document.getElementById('loadingSpinner').style.display = 'none';
                        document.getElementById('fineTableContainer').style.display = 'block';
                    } else {
                        showAlert('Error loading fine records: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to load fine records', 'danger');
                });
        }

        // Populate fine table
        // quick templating using innerHTML (fine for admin internal screens)
        function populateFineTable(fines) {
            const tbody = document.getElementById('fineTableBody');
            tbody.innerHTML = '';

            fines.forEach(fine => {
                const row = document.createElement('tr');
                const statusClass = `status-${fine.status}`;
                
                row.innerHTML = `
                    <td>
                        <strong>${fine.user_name}</strong><br>
                        <small class="text-muted">${fine.user_type}</small>
                    </td>
                    <td>${fine.book_title}</td>
                    <td>
                        <span class="badge bg-warning">${fine.days_overdue} days</span>
                    </td>
                    <td>
                        <strong class="text-danger">₹${parseFloat(fine.fine_amount).toFixed(2)}</strong>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${fine.status}</span>
                    </td>
                    <td>${new Date(fine.calculated_date).toLocaleDateString()}</td>
                    <td>
                        ${fine.status === 'pending' || fine.status === 'overdue' ? `
                            <button class="btn btn-sm btn-success me-1" onclick="openPaymentModal(${fine.fine_id})">
                                <i class="fas fa-money-bill"></i> Pay
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="openWaiverModal(${fine.fine_id})">
                                <i class="fas fa-times"></i> Waive
                            </button>
                        ` : `
                            <span class="text-muted">No actions</span>
                        `}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Calculate overdue fines
        // triggers server to compute + store fines for overdue issues
        function calculateOverdueFines() {
            showAlert('Calculating overdue fines...', 'info');
            
            fetch('api/fine_data.php?action=calculate_overdue', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadFineRecords(); // Reload table
                        setTimeout(() => location.reload(), 2000); // Refresh stats
                    } else {
                        showAlert('Error: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to calculate overdue fines', 'danger');
                });
        }

        // Send fine reminders
        // backend will send emails (uses PHPMailer in includes/email_functions.php)
        function sendFineReminders() {
            showAlert('Sending fine reminders...', 'info');
            
            fetch('api/fine_data.php?action=send_reminders', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                    } else {
                        showAlert('Error: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to send reminders', 'danger');
                });
        }

        // Open detailed report (static HTML report view)
        function openDetailedReport() {
            window.open('reports/fine-calculation-report.html', '_blank');
        }

        // Export fine data
        // TODO: hook to backend CSV later; for now just shows a friendly note
        function exportFineData() {
            showAlert('Preparing export...', 'info');
            // This would typically generate and download a CSV/Excel file
            setTimeout(() => {
                showAlert('Export functionality will be implemented based on your preferred format', 'warning');
            }, 1000);
        }

        // Open payment modal
        // resets fields and opens Bootstrap modal
        function openPaymentModal(fineId) {
            document.getElementById('paymentFineId').value = fineId;
            document.getElementById('paymentMethod').value = 'cash';
            document.getElementById('paymentNotes').value = '';
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        // Process payment
        // posts data to API -> action=payment
        function processPayment() {
            const fineId = document.getElementById('paymentFineId').value;
            const method = document.getElementById('paymentMethod').value;
            const notes = document.getElementById('paymentNotes').value;

            const formData = new FormData();
            formData.append('fine_id', fineId);
            formData.append('payment_method', method);
            formData.append('notes', notes);

            fetch('api/fine_data.php?action=payment', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        loadFineRecords(); // Reload table
                        setTimeout(() => location.reload(), 1500); // Refresh stats
                    } else {
                        showAlert('Error: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to process payment', 'danger');
                });
        }

        // Open waiver modal
        function openWaiverModal(fineId) {
            document.getElementById('waiverFineId').value = fineId;
            document.getElementById('waiverReason').value = '';
            new bootstrap.Modal(document.getElementById('waiverModal')).show();
        }

        // Process waiver
        // simple required check on reason; then posts to API -> action=waive
        function processWaiver() {
            const fineId = document.getElementById('waiverFineId').value;
            const reason = document.getElementById('waiverReason').value;

            if (!reason.trim()) {
                showAlert('Please provide a reason for waiving the fine', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('fine_id', fineId);
            formData.append('reason', reason);

            fetch('api/fine_data.php?action=waive', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('waiverModal')).hide();
                        loadFineRecords(); // Reload table
                        setTimeout(() => location.reload(), 1500); // Refresh stats
                    } else {
                        showAlert('Error: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to process waiver', 'danger');
                });
        }

        // Show alert message
        // helper for consistent UI alerts (auto-dismiss after 5s)
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-custom`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alertDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
