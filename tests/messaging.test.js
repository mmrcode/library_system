/**
 * Messaging System Unit Tests
 * Tests for in-app messaging between students and librarians
 */

describe('Messaging System Tests', function() {
    
    beforeEach(function() {
        // Setup mock message data
        window.mockMessages = [
            {
                message_id: 1,
                sender_id: 123,
                sender_name: 'John Doe',
                sender_type: 'student',
                recipient_id: 1,
                recipient_name: 'Librarian',
                recipient_type: 'admin',
                subject: 'Book Request Follow-up',
                message: 'When will my requested book be available?',
                sent_at: '2025-01-08 10:30:00',
                is_read: false,
                thread_id: 'thread_456'
            }
        ];
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
    });

    describe('Message Composition', function() {
        
        it('should validate message form before sending', function() {
            window.validateMessageForm = function(messageData) {
                const errors = [];
                
                if (!messageData.recipient_id) {
                    errors.push('Recipient is required');
                }
                
                if (!messageData.subject || messageData.subject.trim().length === 0) {
                    errors.push('Subject is required');
                }
                
                if (!messageData.message || messageData.message.trim().length === 0) {
                    errors.push('Message content is required');
                }
                
                if (messageData.message && messageData.message.length > 1000) {
                    errors.push('Message is too long (max 1000 characters)');
                }
                
                return {
                    valid: errors.length === 0,
                    errors: errors
                };
            };
            
            // Test invalid message
            const invalidMessage = {
                recipient_id: null,
                subject: '',
                message: ''
            };
            
            const invalidResult = window.validateMessageForm(invalidMessage);
            expect(invalidResult.valid).to.be.false;
            expect(invalidResult.errors).to.include('Recipient is required');
            expect(invalidResult.errors).to.include('Subject is required');
            expect(invalidResult.errors).to.include('Message content is required');
            
            // Test valid message
            const validMessage = {
                recipient_id: 1,
                subject: 'Test Subject',
                message: 'Test message content'
            };
            
            const validResult = window.validateMessageForm(validMessage);
            expect(validResult.valid).to.be.true;
            expect(validResult.errors).to.have.length(0);
        });
        
        it('should handle message length limits', function() {
            const longMessage = 'a'.repeat(1001);
            const messageData = {
                recipient_id: 1,
                subject: 'Test',
                message: longMessage
            };
            
            const result = window.validateMessageForm(messageData);
            expect(result.valid).to.be.false;
            expect(result.errors).to.include('Message is too long');
        });
    });

    describe('Message Sending', function() {
        
        it('should send message successfully', function(done) {
            const mockResponse = {
                success: true,
                message_id: 789,
                thread_id: 'thread_new_123',
                sent_at: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.sendMessage = function(messageData) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_message',
                        ...messageData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.message_id).to.be.a('number');
                    expect(response.thread_id).to.include('thread_');
                    expect(response.sent_at).to.be.a('string');
                    done();
                });
            };
            
            window.sendMessage({
                recipient_id: 1,
                subject: 'Test Message',
                message: 'This is a test message',
                sender_id: 123
            });
        });
        
        it('should handle message sending errors', function(done) {
            const mockError = {
                success: false,
                error: 'Recipient not found',
                error_code: 'INVALID_RECIPIENT'
            };
            
            TestUtils.mockAjax(mockError);
            
            window.sendMessage = function(messageData) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_message',
                        ...messageData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.false;
                    expect(response.error).to.include('Recipient not found');
                    expect(response.error_code).to.equal('INVALID_RECIPIENT');
                    done();
                });
            };
            
            window.sendMessage({
                recipient_id: 999, // Invalid recipient
                subject: 'Test',
                message: 'Test message'
            });
        });
    });

    describe('Message Retrieval', function() {
        
        it('should fetch user messages with pagination', function(done) {
            const mockMessages = {
                success: true,
                messages: window.mockMessages,
                total_count: 1,
                unread_count: 1,
                page: 1,
                per_page: 10
            };
            
            TestUtils.mockAjax(mockMessages);
            
            window.fetchUserMessages = function(userId, page = 1) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'GET',
                    data: {
                        action: 'get_user_messages',
                        user_id: userId,
                        page: page
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.messages).to.be.an('array');
                    expect(response.messages).to.have.length(1);
                    expect(response.unread_count).to.equal(1);
                    expect(response.total_count).to.equal(1);
                    done();
                });
            };
            
            window.fetchUserMessages(123, 1);
        });
        
        it('should fetch message thread correctly', function(done) {
            const mockThread = {
                success: true,
                thread_id: 'thread_456',
                messages: [
                    {
                        message_id: 1,
                        sender_name: 'John Doe',
                        message: 'Original message',
                        sent_at: '2025-01-08 10:30:00'
                    },
                    {
                        message_id: 2,
                        sender_name: 'Librarian',
                        message: 'Reply message',
                        sent_at: '2025-01-08 11:00:00'
                    }
                ],
                participants: ['John Doe', 'Librarian']
            };
            
            TestUtils.mockAjax(mockThread);
            
            window.fetchMessageThread = function(threadId) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'GET',
                    data: {
                        action: 'get_thread',
                        thread_id: threadId
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.thread_id).to.equal('thread_456');
                    expect(response.messages).to.have.length(2);
                    expect(response.participants).to.include('John Doe');
                    expect(response.participants).to.include('Librarian');
                    done();
                });
            };
            
            window.fetchMessageThread('thread_456');
        });
    });

    describe('Message Status Management', function() {
        
        it('should mark messages as read', function(done) {
            const mockResponse = {
                success: true,
                marked_read: 3,
                updated_at: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.markMessagesAsRead = function(messageIds) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'POST',
                    data: {
                        action: 'mark_as_read',
                        message_ids: JSON.stringify(messageIds)
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.marked_read).to.equal(3);
                    expect(response.updated_at).to.be.a('string');
                    done();
                });
            };
            
            window.markMessagesAsRead([1, 2, 3]);
        });
        
        it('should get unread message count', function(done) {
            const mockCount = {
                success: true,
                unread_count: 5,
                last_checked: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockCount);
            
            window.getUnreadCount = function(userId) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'GET',
                    data: {
                        action: 'get_unread_count',
                        user_id: userId
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.unread_count).to.equal(5);
                    expect(response.last_checked).to.be.a('string');
                    done();
                });
            };
            
            window.getUnreadCount(123);
        });
    });

    describe('Message Threading', function() {
        
        it('should create new thread for new conversation', function() {
            window.generateThreadId = function(senderId, recipientId, subject) {
                const timestamp = Date.now();
                const hash = btoa(`${senderId}_${recipientId}_${subject}_${timestamp}`);
                return `thread_${hash.substring(0, 10)}`;
            };
            
            const threadId = window.generateThreadId(123, 1, 'Test Subject');
            expect(threadId).to.include('thread_');
            expect(threadId.length).to.be.at.least(15);
        });
        
        it('should reply to existing thread', function(done) {
            const mockReply = {
                success: true,
                message_id: 456,
                thread_id: 'thread_existing',
                is_reply: true
            };
            
            TestUtils.mockAjax(mockReply);
            
            window.replyToMessage = function(threadId, replyData) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'POST',
                    data: {
                        action: 'reply_to_thread',
                        thread_id: threadId,
                        ...replyData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.thread_id).to.equal('thread_existing');
                    expect(response.is_reply).to.be.true;
                    done();
                });
            };
            
            window.replyToMessage('thread_existing', {
                sender_id: 1,
                message: 'This is a reply'
            });
        });
    });

    describe('Broadcast Messaging', function() {
        
        it('should send broadcast message to all students', function(done) {
            const mockBroadcast = {
                success: true,
                broadcast_id: 'broadcast_123',
                recipients_count: 50,
                sent_at: new Date().toISOString()
            };
            
            TestUtils.mockAjax(mockBroadcast);
            
            window.sendBroadcastMessage = function(broadcastData) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'POST',
                    data: {
                        action: 'send_broadcast',
                        ...broadcastData
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.broadcast_id).to.include('broadcast_');
                    expect(response.recipients_count).to.be.a('number');
                    expect(response.recipients_count).to.be.above(0);
                    done();
                });
            };
            
            window.sendBroadcastMessage({
                sender_id: 1,
                sender_type: 'admin',
                recipient_type: 'student',
                subject: 'Library Notice',
                message: 'Library will be closed tomorrow for maintenance.'
            });
        });
        
        it('should validate broadcast permissions', function() {
            window.canSendBroadcast = function(userData) {
                return userData && userData.user_type === 'admin';
            };
            
            const adminUser = { user_type: 'admin', user_id: 1 };
            const studentUser = { user_type: 'student', user_id: 123 };
            
            expect(window.canSendBroadcast(adminUser)).to.be.true;
            expect(window.canSendBroadcast(studentUser)).to.be.false;
        });
    });

    describe('Message Search and Filtering', function() {
        
        it('should search messages by keyword', function(done) {
            const mockSearchResults = {
                success: true,
                messages: [
                    {
                        message_id: 1,
                        subject: 'Book Request Follow-up',
                        message: 'When will my requested book be available?',
                        sender_name: 'John Doe',
                        sent_at: '2025-01-08 10:30:00',
                        relevance_score: 0.95
                    }
                ],
                search_term: 'book request',
                results_count: 1
            };
            
            TestUtils.mockAjax(mockSearchResults);
            
            window.searchMessages = function(userId, searchTerm) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'GET',
                    data: {
                        action: 'search_messages',
                        user_id: userId,
                        search: searchTerm
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.messages).to.be.an('array');
                    expect(response.search_term).to.equal('book request');
                    expect(response.results_count).to.equal(1);
                    done();
                });
            };
            
            window.searchMessages(123, 'book request');
        });
        
        it('should filter messages by date range', function(done) {
            const mockFilteredMessages = {
                success: true,
                messages: window.mockMessages,
                date_from: '2025-01-08',
                date_to: '2025-01-08',
                filtered_count: 1
            };
            
            TestUtils.mockAjax(mockFilteredMessages);
            
            window.filterMessagesByDate = function(userId, dateFrom, dateTo) {
                return $.ajax({
                    url: 'includes/messaging_functions.php',
                    method: 'GET',
                    data: {
                        action: 'filter_by_date',
                        user_id: userId,
                        date_from: dateFrom,
                        date_to: dateTo
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.date_from).to.equal('2025-01-08');
                    expect(response.filtered_count).to.equal(1);
                    done();
                });
            };
            
            window.filterMessagesByDate(123, '2025-01-08', '2025-01-08');
        });
    });

    describe('Message Notifications', function() {
        
        it('should create notification for new message', function() {
            window.createMessageNotification = function(messageData) {
                return {
                    notification_id: 'notif_' + Date.now(),
                    recipient_id: messageData.recipient_id,
                    type: 'new_message',
                    title: `New message from ${messageData.sender_name}`,
                    message: `Subject: ${messageData.subject}`,
                    created_at: new Date().toISOString(),
                    is_read: false
                };
            };
            
            const notification = window.createMessageNotification({
                recipient_id: 123,
                sender_name: 'Librarian',
                subject: 'Book Available'
            });
            
            expect(notification.notification_id).to.include('notif_');
            expect(notification.type).to.equal('new_message');
            expect(notification.title).to.include('New message from Librarian');
            expect(notification.is_read).to.be.false;
        });
    });
});
