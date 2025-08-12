<?php
/**
 * Fine System Setup Script
 * Initializes the fine calculation and tracking system for the Library Management System
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied. Admin login required.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine System Setup - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 40px;
        }
        .step {
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #3498db;
            background: #f8f9fa;
        }
        .step.success {
            border-left-color: #27ae60;
            background: #d4edda;
        }
        .step.error {
            border-left-color: #e74c3c;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1><i class="fas fa-calculator"></i> Fine System Setup</h1>
            <p>Initialize Fine Calculation & Tracking System</p>
        </div>
        
        <div class="content">
            <div id="setupProgress">
                <div class="step">
                    <h5>🔄 Initializing Fine System...</h5>
                    <p>Setting up database tables and configuration</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Start setup process
        document.addEventListener('DOMContentLoaded', function() {
            setupFineSystem();
        });

        async function setupFineSystem() {
            const progressDiv = document.getElementById('setupProgress');
            
            try {
                // Step 1: Create database tables
                await updateProgress('Creating database tables...', 'info');
                const response = await fetch('setup_fine_system.php?action=create_tables', {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    await updateProgress('✅ Database tables created successfully', 'success');
                } else {
                    throw new Error(result.error);
                }

                // Step 2: Insert default settings
                await updateProgress('Configuring default fine rates...', 'info');
                const settingsResponse = await fetch('setup_fine_system.php?action=insert_settings', {
                    method: 'POST'
                });
                const settingsResult = await settingsResponse.json();
                
                if (settingsResult.success) {
                    await updateProgress('✅ Default fine rates configured', 'success');
                } else {
                    throw new Error(settingsResult.error);
                }

                // Step 3: Calculate existing overdue fines
                await updateProgress('Calculating existing overdue fines...', 'info');
                const calcResponse = await fetch('admin/api/fine_data.php?action=calculate_overdue', {
                    method: 'POST'
                });
                const calcResult = await calcResponse.json();
                
                if (calcResult.success) {
                    await updateProgress(`✅ Calculated fines for existing overdue books: ${calcResult.data.length} items`, 'success');
                } else {
                    await updateProgress('⚠️ Fine calculation completed with warnings', 'warning');
                }

                // Final success message
                await updateProgress('🎉 Fine System Setup Complete!', 'success');
                progressDiv.innerHTML += `
                    <div class="step success">
                        <h5>✅ Setup Completed Successfully</h5>
                        <p>The fine calculation system is now ready to use.</p>
                        <div class="mt-3">
                            <a href="admin/fine_report.php" class="btn btn-primary">Open Fine Report</a>
                            <a href="admin/dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                        </div>
                    </div>
                `;

            } catch (error) {
                await updateProgress(`❌ Setup failed: ${error.message}`, 'error');
                progressDiv.innerHTML += `
                    <div class="step error">
                        <h5>❌ Setup Failed</h5>
                        <p>Please check the error message above and try again.</p>
                        <button class="btn btn-warning" onclick="location.reload()">Retry Setup</button>
                    </div>
                `;
            }
        }

        function updateProgress(message, type) {
            return new Promise(resolve => {
                const progressDiv = document.getElementById('setupProgress');
                const stepClass = type === 'success' ? 'success' : type === 'error' ? 'error' : '';
                
                progressDiv.innerHTML += `
                    <div class="step ${stepClass}">
                        <p>${message}</p>
                    </div>
                `;
                
                setTimeout(resolve, 500);
            });
        }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'create_tables':
                // Create fines table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS fines (
                        fine_id INT AUTO_INCREMENT PRIMARY KEY,
                        issue_id INT NOT NULL,
                        user_id INT NOT NULL,
                        book_id INT NOT NULL,
                        fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        days_overdue INT NOT NULL DEFAULT 0,
                        status ENUM('pending', 'paid', 'waived', 'overdue') DEFAULT 'pending',
                        calculated_date DATETIME NOT NULL,
                        paid_date DATETIME NULL,
                        waived_date DATETIME NULL,
                        payment_method VARCHAR(50) NULL,
                        payment_notes TEXT NULL,
                        waived_reason TEXT NULL,
                        last_reminder_date DATETIME NULL,
                        reminder_count INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(user_id),
                        FOREIGN KEY (book_id) REFERENCES books(book_id),
                        INDEX idx_user_status (user_id, status),
                        INDEX idx_calculated_date (calculated_date)
                    )
                ");
                
                // Create fine transactions table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS fine_transactions (
                        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                        fine_id INT NOT NULL,
                        transaction_type ENUM('payment', 'waiver', 'adjustment') NOT NULL,
                        transaction_method VARCHAR(50) NOT NULL,
                        notes TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (fine_id) REFERENCES fines(fine_id)
                    )
                ");
                
                // Create book_issues table if it doesn't exist (for testing)
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS book_issues (
                        issue_id INT AUTO_INCREMENT PRIMARY KEY,
                        book_id INT NOT NULL,
                        user_id INT NOT NULL,
                        issue_date DATE NOT NULL,
                        due_date DATE NOT NULL,
                        return_date DATE NULL,
                        status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (book_id) REFERENCES books(book_id),
                        FOREIGN KEY (user_id) REFERENCES users(user_id)
                    )
                ");
                
                echo json_encode(['success' => true, 'message' => 'Tables created successfully']);
                break;
                
            case 'insert_settings':
                // Insert default fine settings
                $settings = [
                    ['fine_regular_book', '1.00'],
                    ['fine_reference_book', '2.00'],
                    ['fine_journal', '1.50'],
                    ['fine_magazine', '0.50'],
                    ['fine_maximum_fine', '50.00'],
                    ['fine_grace_period', '2']
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, created_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                
                foreach ($settings as $setting) {
                    $stmt->execute($setting);
                }
                
                // Insert some sample overdue book issues for testing
                $sampleIssues = [
                    [1, 1, '2024-12-01', '2024-12-15'], // Book 1, User 1, overdue
                    [2, 2, '2024-12-10', '2024-12-24'], // Book 2, User 2, overdue
                    [3, 1, '2024-11-20', '2024-12-04'], // Book 3, User 1, very overdue
                ];
                
                $issueStmt = $pdo->prepare("
                    INSERT IGNORE INTO book_issues (book_id, user_id, issue_date, due_date, status) 
                    VALUES (?, ?, ?, ?, 'issued')
                ");
                
                foreach ($sampleIssues as $issue) {
                    $issueStmt->execute($issue);
                }
                
                echo json_encode(['success' => true, 'message' => 'Settings configured successfully']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
