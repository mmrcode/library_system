<?php
/**
 * Admin Footer Template
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}
?>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; 2025 Library Management System - Mohammad Muqsit Raja (BCA22739)
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted">
                        University of Mysore | Version <?php echo APP_VERSION; ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for enhanced functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/admin.js"></script>
    
    <script>
        // Global JavaScript functions for admin panel
        
        // Confirm delete action
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Show loading spinner
        function showLoading(button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            button.disabled = true;
            
            // Return function to restore button
            return function() {
                button.innerHTML = originalText;
                button.disabled = false;
            };
        }
        
        // Format currency
        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toFixed(2);
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Initialize popovers
        document.addEventListener('DOMContentLoaded', function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Session timeout warning
        let sessionTimeout = <?php echo SESSION_TIMEOUT; ?> * 1000; // Convert to milliseconds
        let warningTime = sessionTimeout - (5 * 60 * 1000); // 5 minutes before timeout
        
        setTimeout(function() {
            if (confirm('Your session will expire in 5 minutes. Do you want to extend your session?')) {
                // Make an AJAX call to extend session
                fetch('../ajax/extend_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
            }
        }, warningTime);
        
        // Auto-logout on session timeout
        setTimeout(function() {
            alert('Your session has expired. You will be redirected to the login page.');
            window.location.href = '../logout.php';
        }, sessionTimeout);
    </script>
</body>
</html>
