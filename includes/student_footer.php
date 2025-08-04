<?php
/**
 * Student Footer Template
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
                        University of Mysore | Student Portal
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for enhanced functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Student Portal JavaScript -->
    <script src="../assets/js/student.js"></script>
    
    <script>
        // Global JavaScript functions for student portal
        
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
        
        // Format currency
        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toFixed(2);
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-IN');
        }
        
        // Session timeout warning (student sessions are typically longer)
        let sessionTimeout = <?php echo SESSION_TIMEOUT; ?> * 1000; // Convert to milliseconds
        let warningTime = sessionTimeout - (10 * 60 * 1000); // 10 minutes before timeout
        
        setTimeout(function() {
            if (confirm('Your session will expire in 10 minutes. Do you want to extend your session?')) {
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
        
        // Check for overdue books notification
        function checkOverdueBooks() {
            fetch('../student/ajax/check_overdue.php')
                .then(response => response.json())
                .then(data => {
                    if (data.overdue_count > 0) {
                        showOverdueNotification(data.overdue_count);
                    }
                })
                .catch(error => {
                    // Silent error handling for overdue check
                });
        }
        
        function showOverdueNotification(count) {
            // Update notification badge
            const notificationBadge = document.querySelector('#notificationDropdown .badge');
            if (notificationBadge) {
                notificationBadge.textContent = count;
                notificationBadge.style.display = 'inline';
            }
        }
        
        // Check for overdue books every 30 minutes
        setInterval(checkOverdueBooks, 30 * 60 * 1000);
        
        // Smooth scrolling for anchor links
                // Smooth scrolling for anchor links with the .smooth-scroll class
        document.querySelectorAll('a.smooth-scroll[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
