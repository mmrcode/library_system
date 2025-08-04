-- Clean Sample Data for Library Management System
-- Created by: Mohammad Muqsit Raja (BCA22739)
-- University of Mysore - 2025
-- This version handles existing data gracefully

USE library_management;

-- Clear existing sample data first (optional - uncomment if needed)
-- DELETE FROM activity_logs WHERE user_id > 0;
-- DELETE FROM fines WHERE user_id > 0;
-- DELETE FROM book_issues WHERE user_id > 0;
-- DELETE FROM books WHERE book_id > 0;
-- DELETE FROM users WHERE user_id > 1;
-- DELETE FROM categories WHERE category_id > 0;

-- Insert sample users (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO users (username, password, full_name, email, phone, address, user_type, registration_number, department, year_of_study, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@university.edu', '9876543210', 'University Campus', 'admin', NULL, NULL, NULL, 'active'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john.doe@student.university.edu', '9876543211', '123 Student Hostel', 'student', 'BCA22001', 'Computer Science', 3, 'active'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane.smith@student.university.edu', '9876543212', '124 Student Hostel', 'student', 'BCA22002', 'Computer Science', 2, 'active'),
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Robert Johnson', 'robert.johnson@university.edu', '9876543213', 'Faculty Quarters', 'faculty', 'FAC001', 'Computer Science', NULL, 'active'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'alice.johnson@student.university.edu', '9876543214', '125 Student Hostel', 'student', 'BCA22003', 'Computer Science', 1, 'active'),
('student4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Brown', 'michael.brown@student.university.edu', '9876543215', '126 Student Hostel', 'student', 'BCA22004', 'Information Technology', 2, 'active'),
('student5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Wilson', 'sarah.wilson@student.university.edu', '9876543216', '127 Student Hostel', 'student', 'BCA22005', 'Computer Science', 3, 'active'),
('faculty2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Emily Davis', 'emily.davis@university.edu', '9876543217', 'Faculty Quarters', 'faculty', 'FAC002', 'Mathematics', NULL, 'active'),
('librarian1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Garcia', 'maria.garcia@university.edu', '9876543218', 'Library Office', 'admin', 'LIB001', 'Library Science', NULL, 'active'),
('student6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Kumar', 'david.kumar@student.university.edu', '9876543219', '128 Student Hostel', 'student', 'BCA22006', 'Computer Science', 2, 'active'),
('student7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Sharma', 'priya.sharma@student.university.edu', '9876543220', '129 Student Hostel', 'student', 'BCA22007', 'Information Technology', 1, 'active'),
('student8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Patel', 'rajesh.patel@student.university.edu', '9876543221', '130 Student Hostel', 'student', 'BCA22008', 'Computer Science', 3, 'active'),
('student9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anita Singh', 'anita.singh@student.university.edu', '9876543222', '131 Student Hostel', 'student', 'BCA22009', 'Information Technology', 2, 'active'),
('student10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vikram Reddy', 'vikram.reddy@student.university.edu', '9876543223', '132 Student Hostel', 'student', 'BCA22010', 'Computer Science', 1, 'active'),
('faculty3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Suresh Nair', 'suresh.nair@university.edu', '9876543224', 'Faculty Quarters', 'faculty', 'FAC003', 'Physics', NULL, 'active'),
('faculty4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Kavitha Rao', 'kavitha.rao@university.edu', '9876543225', 'Faculty Quarters', 'faculty', 'FAC004', 'Literature', NULL, 'active');

-- Insert sample categories (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO categories (category_name, description) VALUES
('Computer Science', 'Books related to computer science, programming, and technology'),
('Mathematics', 'Mathematics and statistical books'),
('Physics', 'Physics and applied physics books'),
('Literature', 'English and regional literature books'),
('History', 'Historical books and references'),
('Business', 'Business management and economics books');

-- Insert sample books (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO books (isbn, title, author, publisher, publication_year, category_id, edition, pages, language, total_copies, available_copies, location, description, status) VALUES
('978-0134685991', 'Effective Java', 'Joshua Bloch', 'Addison-Wesley', 2018, 1, '3rd Edition', 412, 'English', 5, 5, 'Section A-1', 'Best practices for Java programming', 'active'),
('978-0132350884', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 1, '1st Edition', 464, 'English', 3, 3, 'Section A-2', 'A handbook of agile software craftsmanship', 'active'),
('978-0321573513', 'Algorithms', 'Robert Sedgewick', 'Addison-Wesley', 2011, 1, '4th Edition', 976, 'English', 4, 4, 'Section A-3', 'Comprehensive algorithms textbook', 'active'),
('978-0262033848', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 1, '3rd Edition', 1312, 'English', 2, 2, 'Section A-4', 'The definitive algorithms reference', 'active'),
('978-0134494166', 'The Clean Coder', 'Robert C. Martin', 'Prentice Hall', 2011, 1, '1st Edition', 256, 'English', 3, 3, 'Section A-5', 'A code of conduct for professional programmers', 'active'),
('978-0321125217', 'Domain-Driven Design', 'Eric Evans', 'Addison-Wesley', 2003, 1, '1st Edition', 560, 'English', 2, 2, 'Section A-6', 'Tackling complexity in the heart of software', 'active'),
('978-0134052502', 'Calculus: Early Transcendentals', 'James Stewart', 'Cengage Learning', 2015, 2, '8th Edition', 1368, 'English', 6, 6, 'Section B-1', 'Comprehensive calculus textbook', 'active'),
('978-0321749086', 'University Physics', 'Hugh D. Young', 'Pearson', 2015, 3, '14th Edition', 1632, 'English', 4, 4, 'Section C-1', 'Modern physics for university students', 'active'),
('978-0143039433', 'The God of Small Things', 'Arundhati Roy', 'Penguin Books', 1997, 4, '1st Edition', 340, 'English', 5, 5, 'Section D-1', 'Booker Prize winning novel', 'active'),
('978-0143420415', 'A Brief History of Time', 'Stephen Hawking', 'Bantam Books', 1988, 3, '1st Edition', 256, 'English', 3, 3, 'Section C-2', 'Popular science book about cosmology', 'active'),
('978-0596009205', 'Head First Design Patterns', 'Eric Freeman', 'O\'Reilly Media', 2004, 1, '1st Edition', 694, 'English', 4, 4, 'Section A-7', 'Design patterns in object-oriented programming', 'active'),
('978-0135957059', 'The Pragmatic Programmer', 'David Thomas', 'Addison-Wesley', 2019, 1, '2nd Edition', 352, 'English', 3, 3, 'Section A-8', 'Your journey to mastery', 'active'),
('978-0321356680', 'Effective C++', 'Scott Meyers', 'Addison-Wesley', 2005, 1, '3rd Edition', 320, 'English', 2, 2, 'Section A-9', '55 specific ways to improve your programs', 'active'),
('978-0134757599', 'Refactoring', 'Martin Fowler', 'Addison-Wesley', 2018, 1, '2nd Edition', 448, 'English', 3, 3, 'Section A-10', 'Improving the design of existing code', 'active'),
('978-0321127426', 'Patterns of Enterprise Application Architecture', 'Martin Fowler', 'Addison-Wesley', 2002, 1, '1st Edition', 560, 'English', 2, 2, 'Section A-11', 'Enterprise application architecture patterns', 'active'),
('978-0134494164', 'Linear Algebra and Its Applications', 'David C. Lay', 'Pearson', 2015, 2, '5th Edition', 576, 'English', 5, 5, 'Section B-2', 'Comprehensive linear algebra textbook', 'active'),
('978-0321570567', 'Statistics for Engineers and Scientists', 'William Navidi', 'McGraw-Hill', 2014, 2, '4th Edition', 912, 'English', 4, 4, 'Section B-3', 'Applied statistics for technical fields', 'active'),
('978-0134093413', 'Campbell Biology', 'Jane B. Reece', 'Pearson', 2016, 6, '11th Edition', 1488, 'English', 3, 3, 'Section E-1', 'Comprehensive biology textbook', 'active'),
('978-0321910419', 'Organic Chemistry', 'Paula Yurkanis Bruice', 'Pearson', 2016, 6, '8th Edition', 1344, 'English', 2, 2, 'Section E-2', 'Comprehensive organic chemistry', 'active'),
('978-0143127741', 'Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Harper', 2014, 5, '1st Edition', 464, 'English', 6, 6, 'Section D-2', 'History of human civilization', 'active'),
('978-0143110446', 'The Immortal Life of Henrietta Lacks', 'Rebecca Skloot', 'Crown', 2010, 4, '1st Edition', 384, 'English', 4, 4, 'Section D-3', 'Science and ethics in medical research', 'active'),
('978-0062316097', 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus and Giroux', 2011, 6, '1st Edition', 499, 'English', 3, 3, 'Section F-1', 'Behavioral economics and psychology', 'active'),
('978-0307887894', 'The Lean Startup', 'Eric Ries', 'Crown Business', 2011, 6, '1st Edition', 336, 'English', 4, 4, 'Section F-2', 'Innovation and entrepreneurship', 'active'),
('978-0735619678', 'Code Complete', 'Steve McConnell', 'Microsoft Press', 2004, 1, '2nd Edition', 960, 'English', 2, 2, 'Section A-12', 'A practical handbook of software construction', 'active'),
('978-0201633610', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', 1994, 1, '1st Edition', 395, 'English', 3, 3, 'Section A-13', 'Elements of reusable object-oriented software', 'active'),
('978-0596517748', 'JavaScript: The Good Parts', 'Douglas Crockford', 'O\'Reilly Media', 2008, 1, '1st Edition', 176, 'English', 4, 4, 'Section A-14', 'Unearthing the excellence in JavaScript', 'active'),
('978-0321486813', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'Pearson', 2020, 1, '4th Edition', 1136, 'English', 2, 2, 'Section A-15', 'Comprehensive AI textbook', 'active'),
('978-0262510875', 'Introduction to Machine Learning', 'Ethem Alpaydin', 'MIT Press', 2020, 1, '4th Edition', 712, 'English', 3, 3, 'Section A-16', 'Machine learning fundamentals', 'active'),
('978-1449319793', 'Learning Python', 'Mark Lutz', 'O\'Reilly Media', 2013, 1, '5th Edition', 1648, 'English', 5, 5, 'Section A-17', 'Powerful object-oriented programming', 'active'),
('978-0134093414', 'Discrete Mathematics and Its Applications', 'Kenneth Rosen', 'McGraw-Hill', 2018, 2, '8th Edition', 1072, 'English', 4, 4, 'Section B-4', 'Comprehensive discrete mathematics', 'active'),
('978-0321749087', 'Probability and Statistics', 'Morris DeGroot', 'Pearson', 2013, 2, '4th Edition', 816, 'English', 3, 3, 'Section B-5', 'Mathematical statistics with applications', 'active'),
('978-0134685992', 'Modern Physics', 'Kenneth Krane', 'Wiley', 2019, 3, '4th Edition', 624, 'English', 2, 2, 'Section C-3', 'Introduction to modern physics', 'active'),
('978-0321910420', 'Quantum Physics', 'Robert Eisberg', 'Wiley', 2017, 3, '3rd Edition', 928, 'English', 2, 2, 'Section C-4', 'Atoms, molecules, solids, nuclei', 'active'),
('978-0143039434', 'Pride and Prejudice', 'Jane Austen', 'Penguin Classics', 2003, 4, 'Revised Edition', 432, 'English', 6, 6, 'Section D-4', 'Classic English literature', 'active'),
('978-0143127742', 'To Kill a Mockingbird', 'Harper Lee', 'Harper Perennial', 2006, 4, '50th Anniversary Edition', 376, 'English', 5, 5, 'Section D-5', 'American classic novel', 'active'),
('978-0143110447', '1984', 'George Orwell', 'Penguin Books', 2003, 4, 'Centennial Edition', 328, 'English', 7, 7, 'Section D-6', 'Dystopian social science fiction', 'active'),
('978-0062316098', 'World History: Patterns of Interaction', 'Roger Beck', 'McDougal Littell', 2016, 5, '1st Edition', 1152, 'English', 3, 3, 'Section E-3', 'Comprehensive world history', 'active'),
('978-0307887895', 'The Art of War', 'Sun Tzu', 'Penguin Classics', 2009, 5, 'Deluxe Edition', 273, 'English', 4, 4, 'Section E-4', 'Ancient Chinese military treatise', 'active'),
('978-0735619679', 'Principles of Economics', 'N. Gregory Mankiw', 'Cengage Learning', 2020, 6, '8th Edition', 888, 'English', 4, 4, 'Section F-3', 'Microeconomics and macroeconomics', 'active'),
('978-0134093415', 'Financial Accounting', 'Jerry Weygandt', 'Wiley', 2018, 6, '10th Edition', 1392, 'English', 3, 3, 'Section F-4', 'Comprehensive accounting principles', 'active'),
('978-0321749088', 'Marketing Management', 'Philip Kotler', 'Pearson', 2015, 6, '15th Edition', 832, 'English', 2, 2, 'Section F-5', 'Strategic marketing concepts', 'active');

-- Insert sample system settings (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO system_settings (setting_name, setting_value, description) VALUES
('library_name', 'University of Mysore Library', 'Name of the library'),
('library_address', 'University of Mysore, Mysuru, Karnataka 570006', 'Library address'),
('library_phone', '+91-821-2419661', 'Library contact phone'),
('library_email', 'library@uni-mysore.ac.in', 'Library email address'),
('max_books_per_user', '3', 'Maximum books a user can borrow'),
('loan_duration_days', '14', 'Default loan duration in days'),
('fine_per_day', '2.00', 'Fine amount per day for overdue books'),
('max_renewal_count', '2', 'Maximum number of renewals allowed');

-- Note: Password for all users is 'password123' (except admin which is 'admin123')
-- The hash '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' represents 'password'

-- Success message
SELECT 'Sample data imported successfully! You can now login with the provided credentials.' as Message;
