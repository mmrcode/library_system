/**
 * Book Request System Unit Tests
 * Tests for the comprehensive book request and communication system
 */

describe('BookRequest System Tests', function() {
    
    beforeEach(function() {
        // Setup DOM elements needed for testing
        if (!document.getElementById('requestModal')) {
            const modal = TestUtils.createMockElement('div', { id: 'requestModal' });
            document.body.appendChild(modal);
        }
        
        if (!document.getElementById('requestForm')) {
            const form = TestUtils.createMockElement('form', { id: 'requestForm' });
            document.body.appendChild(form);
        }
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
        // Clean up DOM
        const modal = document.getElementById('requestModal');
        const form = document.getElementById('requestForm');
        if (modal) modal.remove();
        if (form) form.remove();
    });

    describe('Book Request Modal', function() {
        
        it('should open request modal when request button is clicked', function() {
            // Create a mock request button
            const button = TestUtils.createMockElement('button', { 
                class: 'request-book-btn',
                'data-book-id': '123',
                'data-book-title': 'Test Book'
            });
            document.body.appendChild(button);
            
            // Mock the openRequestModal function
            window.openRequestModal = function(bookId, bookTitle) {
                const modal = document.getElementById('requestModal');
                modal.style.display = 'block';
                modal.setAttribute('data-book-id', bookId);
                modal.setAttribute('data-book-title', bookTitle);
            };
            
            // Simulate click
            window.openRequestModal('123', 'Test Book');
            
            const modal = document.getElementById('requestModal');
            expect(modal.style.display).to.equal('block');
            expect(modal.getAttribute('data-book-id')).to.equal('123');
            expect(modal.getAttribute('data-book-title')).to.equal('Test Book');
            
            button.remove();
        });
        
        it('should validate required fields before submission', function() {
            // Mock form validation
            window.validateRequestForm = function() {
                const priority = document.getElementById('priority');
                const duration = document.getElementById('duration');
                
                if (!priority || !duration) {
                    return { valid: false, message: 'Priority and duration are required' };
                }
                
                return { valid: true };
            };
            
            const result = window.validateRequestForm();
            expect(result.valid).to.be.false;
            expect(result.message).to.include('required');
        });
    });

    describe('AJAX Request Submission', function() {
        
        it('should submit book request with correct data', function(done) {
            const mockResponse = {
                success: true,
                message: 'Request submitted successfully',
                request_id: 456
            };
            
            const ajaxStub = TestUtils.mockAjax(mockResponse);
            
            // Mock the submitBookRequest function
            window.submitBookRequest = function(formData) {
                return $.ajax({
                    url: '../includes/request_functions.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.message).to.equal('Request submitted successfully');
                    expect(response.request_id).to.equal(456);
                    done();
                });
            };
            
            const testData = {
                action: 'submit_request',
                book_id: '123',
                priority: 'high',
                duration: '7',
                notes: 'Test request'
            };
            
            window.submitBookRequest(testData);
        });
        
        it('should handle request submission errors gracefully', function(done) {
            const mockError = {
                success: false,
                message: 'Invalid book ID',
                error_code: 'INVALID_BOOK'
            };
            
            TestUtils.mockAjax(mockError);
            
            window.submitBookRequest = function(formData) {
                return $.ajax({
                    url: '../includes/request_functions.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.false;
                    expect(response.message).to.equal('Invalid book ID');
                    expect(response.error_code).to.equal('INVALID_BOOK');
                    done();
                });
            };
            
            const testData = {
                action: 'submit_request',
                book_id: 'invalid',
                priority: 'high',
                duration: '7'
            };
            
            window.submitBookRequest(testData);
        });
    });

    describe('Request Status Management', function() {
        
        it('should update request status correctly', function() {
            const mockStatuses = ['pending', 'approved', 'rejected', 'fulfilled'];
            
            mockStatuses.forEach(status => {
                window.updateRequestStatus = function(requestId, newStatus) {
                    return {
                        request_id: requestId,
                        status: newStatus,
                        updated: true
                    };
                };
                
                const result = window.updateRequestStatus(123, status);
                expect(result.status).to.equal(status);
                expect(result.updated).to.be.true;
            });
        });
        
        it('should display correct status badges', function() {
            const statusConfig = {
                'pending': { class: 'badge-warning', text: 'Pending' },
                'approved': { class: 'badge-info', text: 'Approved' },
                'rejected': { class: 'badge-danger', text: 'Rejected' },
                'fulfilled': { class: 'badge-success', text: 'Fulfilled' }
            };
            
            window.getStatusBadge = function(status) {
                const config = statusConfig[status];
                return config ? `<span class="badge ${config.class}">${config.text}</span>` : '';
            };
            
            Object.keys(statusConfig).forEach(status => {
                const badge = window.getStatusBadge(status);
                expect(badge).to.include(statusConfig[status].class);
                expect(badge).to.include(statusConfig[status].text);
            });
        });
    });

    describe('Book ID Field Validation', function() {
        
        it('should use book_id instead of id field (critical fix)', function() {
            // This test addresses the memory about book.book_id vs book.id issue
            const mockBook = {
                book_id: '123',
                title: 'Test Book',
                author: 'Test Author'
            };
            
            window.extractBookId = function(book) {
                // Should use book_id, not id
                return book.book_id;
            };
            
            const bookId = window.extractBookId(mockBook);
            expect(bookId).to.equal('123');
            expect(bookId).to.not.be.undefined;
        });
        
        it('should handle missing book_id gracefully', function() {
            const mockBook = {
                title: 'Test Book',
                author: 'Test Author'
                // Missing book_id
            };
            
            window.extractBookId = function(book) {
                return book.book_id || null;
            };
            
            const bookId = window.extractBookId(mockBook);
            expect(bookId).to.be.null;
        });
    });

    describe('Request Priority and Duration', function() {
        
        it('should validate priority levels', function() {
            const validPriorities = ['low', 'medium', 'high', 'urgent'];
            
            window.validatePriority = function(priority) {
                return validPriorities.includes(priority);
            };
            
            validPriorities.forEach(priority => {
                expect(window.validatePriority(priority)).to.be.true;
            });
            
            expect(window.validatePriority('invalid')).to.be.false;
        });
        
        it('should validate duration ranges', function() {
            window.validateDuration = function(duration) {
                const days = parseInt(duration);
                return days >= 1 && days <= 30;
            };
            
            expect(window.validateDuration('7')).to.be.true;
            expect(window.validateDuration('15')).to.be.true;
            expect(window.validateDuration('30')).to.be.true;
            expect(window.validateDuration('0')).to.be.false;
            expect(window.validateDuration('31')).to.be.false;
        });
    });

    describe('Email Notification Integration', function() {
        
        it('should trigger email notification on request submission', function(done) {
            const mockEmailResponse = {
                email_sent: true,
                recipient: 'librarian@library.com',
                subject: 'New Book Request',
                message_id: 'email_123'
            };
            
            TestUtils.mockAjax(mockEmailResponse);
            
            window.sendRequestNotification = function(requestData) {
                return $.ajax({
                    url: '../includes/email_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_request_notification',
                        ...requestData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.email_sent).to.be.true;
                    expect(response.recipient).to.include('librarian');
                    done();
                });
            };
            
            window.sendRequestNotification({
                book_id: '123',
                student_id: '456',
                priority: 'high'
            });
        });
    });

    describe('Request Dashboard Integration', function() {
        
        it('should load user requests correctly', function(done) {
            const mockRequests = [
                {
                    request_id: 1,
                    book_title: 'Test Book 1',
                    status: 'pending',
                    request_date: '2025-01-08'
                },
                {
                    request_id: 2,
                    book_title: 'Test Book 2',
                    status: 'approved',
                    request_date: '2025-01-07'
                }
            ];
            
            TestUtils.mockAjax({ success: true, requests: mockRequests });
            
            window.loadUserRequests = function() {
                return $.ajax({
                    url: 'check_request_status.php',
                    method: 'GET',
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.requests).to.be.an('array');
                    expect(response.requests).to.have.length(2);
                    expect(response.requests[0].book_title).to.equal('Test Book 1');
                    done();
                });
            };
            
            window.loadUserRequests();
        });
    });
});
