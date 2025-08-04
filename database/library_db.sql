-- Library Management System Database Schema
-- Created by: Mohammad Muqsit Raja (BCA22739)
-- University of Mysore - 2025

-- Create database
CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Table: users (for both admin and students)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    user_type ENUM('admin', 'student') NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    department VARCHAR(50),
    year_of_study INT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: categories (book categories)
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: books
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    publisher VARCHAR(100),
    publication_year YEAR,
    category_id INT,
    edition VARCHAR(50),
    pages INT,
    language VARCHAR(50) DEFAULT 'English',
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    location VARCHAR(100),
    description TEXT,
    book_image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_isbn (isbn)
);

-- Table: book_issues
CREATE TABLE book_issues (
    issue_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    issued_by INT NOT NULL, -- admin who issued the book
    returned_by INT NULL, -- admin who processed return
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(user_id),
    FOREIGN KEY (returned_by) REFERENCES users(user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_book_id (book_id),
    INDEX idx_issue_date (issue_date),
    INDEX idx_status (status)
);

-- Table: fines
CREATE TABLE fines (
    fine_id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fine_per_day DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    days_overdue INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
    payment_date DATE NULL,
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES book_issues(issue_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Table: system_settings
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: activity_logs
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user
INSERT INTO users (username, password, full_name, email, user_type, status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@library.edu', 'admin', 'active');
-- Default password is 'password' (hashed)

-- Insert default categories
INSERT INTO categories (category_name, description) VALUES
('Computer Science', 'Books related to computer science and programming'),
('Mathematics', 'Mathematics and statistics books'),
('Physics', 'Physics and applied physics books'),
('Chemistry', 'Chemistry and biochemistry books'),
('Literature', 'Literature and language books'),
('History', 'History and social studies books'),
('Biography', 'Biographical and autobiographical books'),
('Reference', 'Reference books, dictionaries, encyclopedias'),
('Fiction', 'Fictional novels and stories'),
('Non-Fiction', 'Non-fictional educational books');

-- Insert default system settings
INSERT INTO system_settings (setting_name, setting_value, description) VALUES
('library_name', 'University Library', 'Name of the library'),
('max_books_per_user', '5', 'Maximum books a user can issue at once'),
('default_issue_days', '14', 'Default number of days for book issue'),
('fine_per_day', '1.00', 'Fine amount per day for overdue books'),
('max_renewal_times', '2', 'Maximum times a book can be renewed'),
('library_email', 'library@university.edu', 'Library contact email'),
('library_phone', '+91-XXXXXXXXXX', 'Library contact phone'),
('working_hours', '9:00 AM - 6:00 PM', 'Library working hours'),
('holidays', 'Sunday', 'Library holidays');

-- Insert sample books
INSERT INTO books (isbn, title, author, publisher, publication_year, category_id, total_copies, available_copies, location) VALUES
('978-0134685991', 'Effective Java', 'Joshua Bloch', 'Addison-Wesley', 2017, 1, 3, 3, 'CS-A-001'),
('978-0132350884', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 1, 2, 2, 'CS-A-002'),
('978-0201633610', 'Design Patterns', 'Gang of Four', 'Addison-Wesley', 1994, 1, 2, 2, 'CS-A-003'),
('978-0321573513', 'Algorithms', 'Robert Sedgewick', 'Addison-Wesley', 2011, 1, 3, 3, 'CS-B-001'),
('978-0262033848', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 1, 4, 4, 'CS-B-002'),
('978-0134494166', 'The Clean Coder', 'Robert C. Martin', 'Prentice Hall', 2011, 1, 2, 2, 'CS-A-004'),
('978-0321125217', 'Domain-Driven Design', 'Eric Evans', 'Addison-Wesley', 2003, 1, 1, 1, 'CS-C-001'),
('978-0596007126', 'Head First Design Patterns', 'Eric Freeman', 'O\'Reilly Media', 2004, 1, 3, 3, 'CS-A-005');

-- Create views for common queries
CREATE VIEW active_issues AS
SELECT 
    bi.issue_id,
    bi.issue_date,
    bi.due_date,
    bi.status,
    b.title,
    b.author,
    u.full_name,
    u.registration_number,
    DATEDIFF(CURDATE(), bi.due_date) as days_overdue
FROM book_issues bi
JOIN books b ON bi.book_id = b.book_id
JOIN users u ON bi.user_id = u.user_id
WHERE bi.status IN ('issued', 'overdue');

CREATE VIEW book_availability AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.total_copies,
    b.available_copies,
    c.category_name,
    CASE 
        WHEN b.available_copies > 0 THEN 'Available'
        ELSE 'Not Available'
    END as availability_status
FROM books b
LEFT JOIN categories c ON b.category_id = c.category_id
WHERE b.status = 'active';

-- Create triggers for automatic updates
DELIMITER //

-- Trigger to update book availability when book is issued
CREATE TRIGGER update_book_availability_on_issue
AFTER INSERT ON book_issues
FOR EACH ROW
BEGIN
    UPDATE books 
    SET available_copies = available_copies - 1 
    WHERE book_id = NEW.book_id;
END//

-- Trigger to update book availability when book is returned
CREATE TRIGGER update_book_availability_on_return
AFTER UPDATE ON book_issues
FOR EACH ROW
BEGIN
    IF OLD.status != 'returned' AND NEW.status = 'returned' THEN
        UPDATE books 
        SET available_copies = available_copies + 1 
        WHERE book_id = NEW.book_id;
    END IF;
END//

-- Trigger to automatically calculate fines for overdue books
CREATE TRIGGER calculate_fine_on_overdue
AFTER UPDATE ON book_issues
FOR EACH ROW
BEGIN
    DECLARE fine_per_day_rate DECIMAL(5,2);
    DECLARE days_overdue INT;
    DECLARE fine_amount DECIMAL(10,2);
    
    -- Get fine rate from settings
    SELECT CAST(setting_value AS DECIMAL(5,2)) INTO fine_per_day_rate
    FROM system_settings WHERE setting_name = 'fine_per_day';
    
    IF NEW.status = 'overdue' AND OLD.status != 'overdue' THEN
        SET days_overdue = DATEDIFF(CURDATE(), NEW.due_date);
        SET fine_amount = days_overdue * fine_per_day_rate;
        
        INSERT INTO fines (issue_id, user_id, fine_amount, fine_per_day, days_overdue, status)
        VALUES (NEW.issue_id, NEW.user_id, fine_amount, fine_per_day_rate, days_overdue, 'pending');
    END IF;
END//

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_books_title_author ON books(title, author);
CREATE INDEX idx_users_registration ON users(registration_number);
CREATE INDEX idx_issues_dates ON book_issues(issue_date, due_date);
CREATE INDEX idx_fines_status ON fines(status, user_id);

-- Grant privileges (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON library_management.* TO 'library_user'@'localhost' IDENTIFIED BY 'library_pass';
-- FLUSH PRIVILEGES;

-- Display success message
SELECT 'Library Management System database created successfully!' as message;
