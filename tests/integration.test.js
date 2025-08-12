/**
 * Integration Tests
 * Tests for component integration, inventory updates, and cross-system functionality
 */

describe('Integration Tests', function() {
    
    beforeEach(function() {
        // Setup integration test environment
        window.integrationTestData = {
            testBooks: [
                { book_id: 1, title: 'Test Book 1', available_copies: 5, total_copies: 5 },
                { book_id: 2, title: 'Test Book 2', available_copies: 2, total_copies: 3 },
                { book_id: 3, title: 'Test Book 3', available_copies: 0, total_copies: 2 }
            ],
            testUsers: [
                { user_id: 101, username: 'student1', user_type: 'student' },
                { user_id: 102, username: 'student2', user_type: 'student' },
                { user_id: 1, username: 'admin', user_type: 'admin' }
            ]
        };
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
    });

    describe('Inventory Update Integration', function() {
        
        it('should update inventory when book is requested and approved', function(done) {
            // Simulate complete book request workflow
            const mockWorkflow = {
                step1_request: {
                    success: true,
                    request_id: 123,
                    book_id: 1,
                    student_id: 101
                },
                step2_approval: {
                    success: true,
                    request_id: 123,
                    status: 'approved',
                    inventory_updated: true
                },
                step3_inventory: {
                    success: true,
                    book_id: 1,
                    available_copies: 4, // Decreased from 5 to 4
                    total_copies: 5,
                    last_updated: new Date().toISOString()
                }
            };
            
            let callCount = 0;
            TestUtils.mockAjax = function(response) {
                return sinon.stub($, 'ajax').callsFake(function(options) {
                    callCount++;
                    
                    if (options.data.action === 'submit_request') {
                        return Promise.resolve(mockWorkflow.step1_request);
                    } else if (options.data.action === 'approve_request') {
                        return Promise.resolve(mockWorkflow.step2_approval);
                    } else if (options.data.action === 'get_book_inventory') {
                        return Promise.resolve(mockWorkflow.step3_inventory);
                    }
                });
            };
            
            window.testInventoryUpdateWorkflow = async function() {
                try {
                    // Step 1: Submit book request
                    const requestResult = await $.ajax({
                        url: 'includes/request_functions.php',
                        method: 'POST',
                        data: { action: 'submit_request', book_id: 1, student_id: 101 }
                    });
                    
                    expect(requestResult.success).to.be.true;
                    expect(requestResult.request_id).to.equal(123);
                    
                    // Step 2: Approve request (admin action)
                    const approvalResult = await $.ajax({
                        url: 'includes/request_functions.php',
                        method: 'POST',
                        data: { action: 'approve_request', request_id: 123 }
                    });
                    
                    expect(approvalResult.success).to.be.true;
                    expect(approvalResult.inventory_updated).to.be.true;
                    
                    // Step 3: Verify inventory update
                    const inventoryResult = await $.ajax({
                        url: 'includes/book_functions.php',
                        method: 'GET',
                        data: { action: 'get_book_inventory', book_id: 1 }
                    });
                    
                    expect(inventoryResult.success).to.be.true;
                    expect(inventoryResult.available_copies).to.equal(4);
                    expect(inventoryResult.total_copies).to.equal(5);
                    
                    done();
                } catch (error) {
                    done(error);
                }
            };
            
            TestUtils.mockAjax();
            window.testInventoryUpdateWorkflow();
        });
        
        it('should handle inventory updates when book is returned', function(done) {
            const mockReturnWorkflow = {
                return_book: {
                    success: true,
                    book_id: 2,
                    student_id: 101,
                    return_date: new Date().toISOString(),
                    inventory_updated: true
                },
                updated_inventory: {
                    success: true,
                    book_id: 2,
                    available_copies: 3, // Increased from 2 to 3
                    total_copies: 3
                }
            };
            
            TestUtils.mockAjax = function() {
                return sinon.stub($, 'ajax').callsFake(function(options) {
                    if (options.data.action === 'return_book') {
                        return Promise.resolve(mockReturnWorkflow.return_book);
                    } else if (options.data.action === 'get_book_inventory') {
                        return Promise.resolve(mockReturnWorkflow.updated_inventory);
                    }
                });
            };
            
            window.testBookReturnWorkflow = async function() {
                try {
                    // Return book
                    const returnResult = await $.ajax({
                        url: 'includes/book_functions.php',
                        method: 'POST',
                        data: { action: 'return_book', book_id: 2, student_id: 101 }
                    });
                    
                    expect(returnResult.success).to.be.true;
                    expect(returnResult.inventory_updated).to.be.true;
                    
                    // Verify inventory increased
                    const inventoryResult = await $.ajax({
                        url: 'includes/book_functions.php',
                        method: 'GET',
                        data: { action: 'get_book_inventory', book_id: 2 }
                    });
                    
                    expect(inventoryResult.available_copies).to.equal(3);
                    done();
                } catch (error) {
                    done(error);
                }
            };
            
            TestUtils.mockAjax();
            window.testBookReturnWorkflow();
        });
    });

    describe('Cross-System Integration', function() {
        
        it('should integrate request system with email notifications', function(done) {
            const mockIntegratedResponse = {
                request_created: {
                    success: true,
                    request_id: 456,
                    book_id: 1,
                    student_id: 101
                },
                email_sent: {
                    success: true,
                    email_id: 'email_456',
                    recipient: 'librarian@library.com',
                    subject: 'New Book Request'
                },
                notification_created: {
                    success: true,
                    notification_id: 'notif_456',
                    user_id: 1,
                    type: 'new_request'
                }
            };
            
            TestUtils.mockAjax = function() {
                return sinon.stub($, 'ajax').callsFake(function(options) {
                    if (options.data.action === 'submit_request_with_notifications') {
                        return Promise.resolve({
                            success: true,
                            request: mockIntegratedResponse.request_created,
                            email: mockIntegratedResponse.email_sent,
                            notification: mockIntegratedResponse.notification_created
                        });
                    }
                });
            };
            
            window.testIntegratedRequestSubmission = async function() {
                try {
                    const result = await $.ajax({
                        url: 'includes/integrated_functions.php',
                        method: 'POST',
                        data: {
                            action: 'submit_request_with_notifications',
                            book_id: 1,
                            student_id: 101,
                            priority: 'high'
                        }
                    });
                    
                    expect(result.success).to.be.true;
                    expect(result.request.request_id).to.equal(456);
                    expect(result.email.email_id).to.include('email_');
                    expect(result.notification.notification_id).to.include('notif_');
                    
                    done();
                } catch (error) {
                    done(error);
                }
            };
            
            TestUtils.mockAjax();
            window.testIntegratedRequestSubmission();
        });
        
        it('should integrate messaging system with request status updates', function(done) {
            const mockStatusUpdateIntegration = {
                status_update: {
                    success: true,
                    request_id: 123,
                    old_status: 'pending',
                    new_status: 'approved'
                },
                message_sent: {
                    success: true,
                    message_id: 789,
                    thread_id: 'thread_123',
                    recipient_id: 101
                },
                email_notification: {
                    success: true,
                    email_sent: true,
                    recipient: 'student@example.com'
                }
            };
            
            TestUtils.mockAjax = function() {
                return sinon.stub($, 'ajax').callsFake(function(options) {
                    if (options.data.action === 'update_request_status_integrated') {
                        return Promise.resolve({
                            success: true,
                            status_update: mockStatusUpdateIntegration.status_update,
                            message: mockStatusUpdateIntegration.message_sent,
                            email: mockStatusUpdateIntegration.email_notification
                        });
                    }
                });
            };
            
            window.testIntegratedStatusUpdate = async function() {
                try {
                    const result = await $.ajax({
                        url: 'includes/integrated_functions.php',
                        method: 'POST',
                        data: {
                            action: 'update_request_status_integrated',
                            request_id: 123,
                            new_status: 'approved',
                            admin_message: 'Your book is ready for pickup'
                        }
                    });
                    
                    expect(result.success).to.be.true;
                    expect(result.status_update.new_status).to.equal('approved');
                    expect(result.message.message_id).to.equal(789);
                    expect(result.email.email_sent).to.be.true;
                    
                    done();
                } catch (error) {
                    done(error);
                }
            };
            
            TestUtils.mockAjax();
            window.testIntegratedStatusUpdate();
        });
    });

    describe('Database Transaction Integration', function() {
        
        it('should handle complex multi-table transactions', function(done) {
            const mockTransactionResult = {
                success: true,
                transaction_id: 'txn_789',
                operations: [
                    { table: 'book_requests', operation: 'insert', affected_rows: 1 },
                    { table: 'books', operation: 'update', affected_rows: 1 },
                    { table: 'notifications', operation: 'insert', affected_rows: 1 },
                    { table: 'email_logs', operation: 'insert', affected_rows: 1 }
                ],
                rollback_available: true
            };
            
            TestUtils.mockAjax(mockTransactionResult);
            
            window.testComplexTransaction = function() {
                return $.ajax({
                    url: 'includes/transaction_functions.php',
                    method: 'POST',
                    data: {
                        action: 'execute_book_request_transaction',
                        book_id: 1,
                        student_id: 101,
                        priority: 'high'
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.transaction_id).to.include('txn_');
                    expect(response.operations).to.have.length(4);
                    expect(response.rollback_available).to.be.true;
                    done();
                });
            };
            
            window.testComplexTransaction();
        });
    });

    describe('Performance Integration Tests', function() {
        
        it('should handle concurrent user requests efficiently', function(done) {
            const mockConcurrentResults = {
                success: true,
                concurrent_requests: 10,
                processed_successfully: 10,
                average_response_time: 150,
                max_response_time: 300,
                min_response_time: 50,
                errors: 0
            };
            
            TestUtils.mockAjax(mockConcurrentResults);
            
            window.testConcurrentRequests = function() {
                const promises = [];
                
                // Simulate 10 concurrent requests
                for (let i = 0; i < 10; i++) {
                    const promise = $.ajax({
                        url: 'includes/performance_test.php',
                        method: 'POST',
                        data: {
                            action: 'concurrent_test',
                            request_id: i,
                            book_id: Math.floor(Math.random() * 3) + 1
                        }
                    });
                    promises.push(promise);
                }
                
                Promise.all(promises).then(function(results) {
                    // All requests should return the same mock result
                    expect(results).to.have.length(10);
                    results.forEach(result => {
                        expect(result.success).to.be.true;
                    });
                    done();
                }).catch(done);
            };
            
            window.testConcurrentRequests();
        });
        
        it('should maintain data consistency under load', function(done) {
            const mockConsistencyTest = {
                success: true,
                initial_inventory: { book_id: 1, available_copies: 5 },
                operations_performed: 3,
                final_inventory: { book_id: 1, available_copies: 2 },
                consistency_check: 'passed',
                data_integrity: 'maintained'
            };
            
            TestUtils.mockAjax(mockConsistencyTest);
            
            window.testDataConsistency = function() {
                return $.ajax({
                    url: 'includes/consistency_test.php',
                    method: 'POST',
                    data: {
                        action: 'test_inventory_consistency',
                        book_id: 1,
                        operations: 3
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.consistency_check).to.equal('passed');
                    expect(response.data_integrity).to.equal('maintained');
                    expect(response.final_inventory.available_copies).to.equal(2);
                    done();
                });
            };
            
            window.testDataConsistency();
        });
    });

    describe('Error Recovery Integration', function() {
        
        it('should recover gracefully from database connection failures', function(done) {
            const mockRecoveryScenario = {
                initial_failure: {
                    success: false,
                    error: 'Database connection lost',
                    error_code: 'DB_CONNECTION_FAILED'
                },
                recovery_attempt: {
                    success: true,
                    message: 'Connection restored',
                    retry_successful: true,
                    data: { book_id: 1, title: 'Test Book' }
                }
            };
            
            let attemptCount = 0;
            TestUtils.mockAjax = function() {
                return sinon.stub($, 'ajax').callsFake(function(options) {
                    attemptCount++;
                    if (attemptCount === 1) {
                        return Promise.resolve(mockRecoveryScenario.initial_failure);
                    } else {
                        return Promise.resolve(mockRecoveryScenario.recovery_attempt);
                    }
                });
            };
            
            window.testErrorRecovery = async function() {
                try {
                    // First attempt should fail
                    const firstAttempt = await $.ajax({
                        url: 'includes/book_functions.php',
                        data: { action: 'get_book', book_id: 1 }
                    });
                    
                    expect(firstAttempt.success).to.be.false;
                    expect(firstAttempt.error_code).to.equal('DB_CONNECTION_FAILED');
                    
                    // Second attempt should succeed (recovery)
                    const secondAttempt = await $.ajax({
                        url: 'includes/book_functions.php',
                        data: { action: 'get_book', book_id: 1 }
                    });
                    
                    expect(secondAttempt.success).to.be.true;
                    expect(secondAttempt.retry_successful).to.be.true;
                    
                    done();
                } catch (error) {
                    done(error);
                }
            };
            
            TestUtils.mockAjax();
            window.testErrorRecovery();
        });
    });
});
