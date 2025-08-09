/**
 * Email System Unit Tests
 * Tests for PHPMailer integration and email notification system
 */

describe('Email System Tests', function() {
    
    beforeEach(function() {
        // Mock email configuration
        window.mockEmailConfig = {
            smtp_host: 'smtp.gmail.com',
            smtp_port: 587,
            smtp_username: 'library@example.com',
            smtp_password: 'password',
            from_email: 'library@example.com',
            from_name: 'Library Management System'
        };
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
    });

    describe('Email Configuration', function() {
        
        it('should validate email configuration settings', function() {
            window.validateEmailConfig = function(config) {
                const errors = [];
                
                if (!config.smtp_host) errors.push('SMTP host is required');
                if (!config.smtp_port || config.smtp_port < 1) errors.push('Valid SMTP port is required');
                if (!config.smtp_username) errors.push('SMTP username is required');
                if (!config.from_email || !/\S+@\S+\.\S+/.test(config.from_email)) {
                    errors.push('Valid from email is required');
                }
                
                return {
                    valid: errors.length === 0,
                    errors: errors
                };
            };
            
            const validConfig = window.mockEmailConfig;
            const result = window.validateEmailConfig(validConfig);
            expect(result.valid).to.be.true;
            expect(result.errors).to.have.length(0);
        });
        
        it('should handle invalid email configuration', function() {
            const invalidConfig = {
                smtp_host: '',
                smtp_port: 0,
                smtp_username: '',
                from_email: 'invalid-email'
            };
            
            const result = window.validateEmailConfig(invalidConfig);
            expect(result.valid).to.be.false;
            expect(result.errors).to.include('SMTP host is required');
            expect(result.errors).to.include('Valid from email is required');
        });
    });

    describe('Book Request Notifications', function() {
        
        it('should send email notification for new book request', function(done) {
            const mockEmailResponse = {
                success: true,
                message_id: 'email_123456',
                recipient: 'librarian@library.com',
                subject: 'New Book Request - JavaScript Guide',
                sent_at: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockEmailResponse);
            
            window.sendBookRequestNotification = function(requestData) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_request_notification',
                        ...requestData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.message_id).to.include('email_');
                    expect(response.recipient).to.include('librarian');
                    expect(response.subject).to.include('New Book Request');
                    done();
                });
            };
            
            window.sendBookRequestNotification({
                student_name: 'John Doe',
                student_email: 'john@student.com',
                book_title: 'JavaScript Guide',
                book_author: 'John Smith',
                priority: 'high',
                duration: 7,
                notes: 'Needed for assignment'
            });
        });
        
        it('should handle email sending failure gracefully', function(done) {
            const mockErrorResponse = {
                success: false,
                error: 'SMTP connection failed',
                error_code: 'SMTP_ERROR',
                retry_after: 300
            };
            
            TestUtils.mockAjax(mockErrorResponse);
            
            window.sendBookRequestNotification = function(requestData) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_request_notification',
                        ...requestData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.false;
                    expect(response.error).to.include('SMTP');
                    expect(response.error_code).to.equal('SMTP_ERROR');
                    expect(response.retry_after).to.be.a('number');
                    done();
                });
            };
            
            window.sendBookRequestNotification({
                student_name: 'John Doe',
                book_title: 'Test Book'
            });
        });
    });

    describe('Request Status Notifications', function() {
        
        it('should send approval notification to student', function(done) {
            const mockResponse = {
                success: true,
                message_id: 'approval_email_789',
                recipient: 'student@example.com',
                subject: 'Book Request Approved - JavaScript Guide'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.sendRequestStatusNotification = function(requestData, status) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_status_notification',
                        status: status,
                        ...requestData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.subject).to.include('Approved');
                    expect(response.recipient).to.include('student');
                    done();
                });
            };
            
            window.sendRequestStatusNotification({
                student_email: 'student@example.com',
                student_name: 'Jane Doe',
                book_title: 'JavaScript Guide',
                request_id: 123
            }, 'approved');
        });
        
        it('should send rejection notification to student', function(done) {
            const mockResponse = {
                success: true,
                message_id: 'rejection_email_790',
                recipient: 'student@example.com',
                subject: 'Book Request Update - JavaScript Guide'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.sendRequestStatusNotification = function(requestData, status) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_status_notification',
                        status: status,
                        ...requestData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.subject).to.include('Update');
                    done();
                });
            };
            
            window.sendRequestStatusNotification({
                student_email: 'student@example.com',
                book_title: 'JavaScript Guide',
                rejection_reason: 'Book currently unavailable'
            }, 'rejected');
        });
    });

    describe('Email Template System', function() {
        
        it('should generate correct email templates for different notification types', function() {
            window.generateEmailTemplate = function(type, data) {
                const templates = {
                    'new_request': {
                        subject: `New Book Request - ${data.book_title}`,
                        body: `A new book request has been submitted by ${data.student_name} for "${data.book_title}" by ${data.book_author}.`
                    },
                    'request_approved': {
                        subject: `Book Request Approved - ${data.book_title}`,
                        body: `Your request for "${data.book_title}" has been approved. Please collect the book from the library.`
                    },
                    'request_rejected': {
                        subject: `Book Request Update - ${data.book_title}`,
                        body: `Your request for "${data.book_title}" has been declined. Reason: ${data.rejection_reason || 'Not specified'}.`
                    }
                };
                
                return templates[type] || null;
            };
            
            const requestData = {
                student_name: 'John Doe',
                book_title: 'JavaScript Guide',
                book_author: 'Jane Smith'
            };
            
            const newRequestTemplate = window.generateEmailTemplate('new_request', requestData);
            expect(newRequestTemplate.subject).to.include('New Book Request');
            expect(newRequestTemplate.body).to.include('John Doe');
            expect(newRequestTemplate.body).to.include('JavaScript Guide');
            
            const approvalTemplate = window.generateEmailTemplate('request_approved', requestData);
            expect(approvalTemplate.subject).to.include('Approved');
            expect(approvalTemplate.body).to.include('approved');
        });
    });

    describe('Email Logging System', function() {
        
        it('should log sent emails to database', function(done) {
            const mockLogResponse = {
                success: true,
                log_id: 456,
                logged_at: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockLogResponse);
            
            window.logEmailSent = function(emailData) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'log_email',
                        ...emailData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.log_id).to.be.a('number');
                    expect(response.logged_at).to.be.a('string');
                    done();
                });
            };
            
            window.logEmailSent({
                recipient: 'test@example.com',
                subject: 'Test Email',
                message_id: 'test_123',
                status: 'sent',
                sent_at: new Date().toISOString()
            });
        });
        
        it('should retrieve email logs with filtering', function(done) {
            const mockLogs = {
                success: true,
                logs: [
                    {
                        log_id: 1,
                        recipient: 'student@example.com',
                        subject: 'Book Request Approved',
                        status: 'sent',
                        sent_at: '2025-01-08 10:30:00'
                    },
                    {
                        log_id: 2,
                        recipient: 'librarian@library.com',
                        subject: 'New Book Request',
                        status: 'sent',
                        sent_at: '2025-01-08 09:15:00'
                    }
                ],
                total_count: 2
            };
            
            TestUtils.mockAjax(mockLogs);
            
            window.getEmailLogs = function(filters = {}) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'GET',
                    data: {
                        action: 'get_email_logs',
                        ...filters
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.logs).to.be.an('array');
                    expect(response.logs).to.have.length(2);
                    expect(response.total_count).to.equal(2);
                    done();
                });
            };
            
            window.getEmailLogs({ date_from: '2025-01-08' });
        });
    });

    describe('Email Validation', function() {
        
        it('should validate email addresses correctly', function() {
            window.validateEmailAddress = function(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            };
            
            expect(window.validateEmailAddress('valid@example.com')).to.be.true;
            expect(window.validateEmailAddress('user.name@domain.co.uk')).to.be.true;
            expect(window.validateEmailAddress('invalid-email')).to.be.false;
            expect(window.validateEmailAddress('@invalid.com')).to.be.false;
            expect(window.validateEmailAddress('invalid@')).to.be.false;
        });
        
        it('should sanitize email content', function() {
            window.sanitizeEmailContent = function(content) {
                return content
                    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                    .replace(/<[^>]*>/g, '')
                    .trim();
            };
            
            const dirtyContent = '<script>alert("xss")</script><p>Hello <b>World</b></p>';
            const cleanContent = window.sanitizeEmailContent(dirtyContent);
            
            expect(cleanContent).to.not.include('<script>');
            expect(cleanContent).to.not.include('<p>');
            expect(cleanContent).to.equal('Hello World');
        });
    });

    describe('Bulk Email Operations', function() {
        
        it('should send bulk notifications to multiple recipients', function(done) {
            const mockBulkResponse = {
                success: true,
                sent_count: 3,
                failed_count: 0,
                results: [
                    { recipient: 'user1@example.com', status: 'sent', message_id: 'bulk_1' },
                    { recipient: 'user2@example.com', status: 'sent', message_id: 'bulk_2' },
                    { recipient: 'user3@example.com', status: 'sent', message_id: 'bulk_3' }
                ]
            };
            
            TestUtils.mockAjax(mockBulkResponse);
            
            window.sendBulkEmails = function(recipients, subject, body) {
                return $.ajax({
                    url: 'includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_bulk_emails',
                        recipients: JSON.stringify(recipients),
                        subject: subject,
                        body: body
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.sent_count).to.equal(3);
                    expect(response.failed_count).to.equal(0);
                    expect(response.results).to.have.length(3);
                    done();
                });
            };
            
            const recipients = [
                'user1@example.com',
                'user2@example.com', 
                'user3@example.com'
            ];
            
            window.sendBulkEmails(recipients, 'Test Subject', 'Test Body');
        });
    });
});
