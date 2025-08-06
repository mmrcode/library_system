-- Book Request and Communication System Enhancement
-- Additional tables for the Library Management System
-- Created by: Mohammad Muqsit Raja (BCA22739)

USE library_management;

-- Table: book_requests (Book request queue system)
CREATE TABLE book_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'fulfilled', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    request_type ENUM('issue', 'reserve', 'purchase') DEFAULT 'issue',
    requested_duration INT DEFAULT 14, -- days
    notes TEXT,
    admin_notes TEXT,
    processed_by INT NULL, -- admin who processed the request
    processed_date TIMESTAMP NULL,
    notification_sent ENUM('no', 'yes') DEFAULT 'no',
    email_sent ENUM('no', 'yes') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_book_id (book_id),
    INDEX idx_status (status),
    INDEX idx_request_date (request_date)
);

-- Table: messages (In-app messaging system)
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message_body TEXT NOT NULL,
    message_type ENUM('general', 'book_request', 'book_issue', 'fine', 'system') DEFAULT 'general',
    related_request_id INT NULL, -- links to book_requests table
    related_issue_id INT NULL, -- links to book_issues table
    is_read ENUM('no', 'yes') DEFAULT 'no',
    is_starred ENUM('no', 'yes') DEFAULT 'no',
    is_archived ENUM('no', 'yes') DEFAULT 'no',
    reply_to INT NULL, -- for threaded conversations
    attachment VARCHAR(255) NULL,
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_date TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_request_id) REFERENCES book_requests(request_id) ON DELETE SET NULL,
    FOREIGN KEY (related_issue_id) REFERENCES book_issues(issue_id) ON DELETE SET NULL,
    FOREIGN KEY (reply_to) REFERENCES messages(message_id) ON DELETE SET NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_sent_date (sent_date),
    INDEX idx_is_read (is_read)
);

-- Table: notifications (System notifications)
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'book_request', 'book_due', 'fine') DEFAULT 'info',
    related_request_id INT NULL,
    related_issue_id INT NULL,
    is_read ENUM('no', 'yes') DEFAULT 'no',
    action_url VARCHAR(255) NULL, -- URL for action button
    action_text VARCHAR(50) NULL, -- Text for action button
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_request_id) REFERENCES book_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (related_issue_id) REFERENCES book_issues(issue_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
);

-- Table: email_logs (Email notification tracking)
CREATE TABLE email_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_email VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    message_body TEXT NOT NULL,
    email_type ENUM('book_request', 'book_approval', 'book_rejection', 'book_due', 'fine_notice', 'general') NOT NULL,
    related_request_id INT NULL,
    related_issue_id INT NULL,
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_date TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_request_id) REFERENCES book_requests(request_id) ON DELETE SET NULL,
    FOREIGN KEY (related_issue_id) REFERENCES book_issues(issue_id) ON DELETE SET NULL,
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_status (status),
    INDEX idx_sent_date (sent_date)
);

-- Table: system_settings (Configuration for email and notifications)
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public ENUM('no', 'yes') DEFAULT 'no', -- whether students can see this setting
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('email_enabled', 'yes', 'boolean', 'Enable email notifications', 'no'),
('smtp_host', 'localhost', 'string', 'SMTP server host', 'no'),
('smtp_port', '587', 'number', 'SMTP server port', 'no'),
('smtp_username', '', 'string', 'SMTP username', 'no'),
('smtp_password', '', 'string', 'SMTP password', 'no'),
('smtp_encryption', 'tls', 'string', 'SMTP encryption (tls/ssl)', 'no'),
('from_email', 'library@yourdomain.com', 'string', 'From email address', 'no'),
('from_name', 'Library Management System', 'string', 'From name', 'yes'),
('librarian_email', 'librarian@yourdomain.com', 'string', 'Main librarian email', 'no'),
('auto_approve_requests', 'no', 'boolean', 'Auto-approve book requests', 'no'),
('max_pending_requests', '5', 'number', 'Maximum pending requests per user', 'yes'),
('request_expiry_days', '7', 'number', 'Days before pending request expires', 'yes'),
('notification_retention_days', '30', 'number', 'Days to keep notifications', 'no');

-- Create indexes for better performance
CREATE INDEX idx_book_requests_status_date ON book_requests(status, request_date);
CREATE INDEX idx_messages_conversation ON messages(sender_id, receiver_id, sent_date);
CREATE INDEX idx_notifications_user_type ON notifications(user_id, notification_type, created_at);

-- Display success message
SELECT 'Book Request and Communication System tables created successfully!' as message;
