# Library Management System - Installation Guide

**Project by:** Mohammad Muqsit Raja  
**Contact:** mmrcode1@gmail.com  
**GitHub:** [github.com/mmrcode](https://github.com/mmrcode)  

## Prerequisites

Before installing the Library Management System, ensure you have the following:

1. **XAMPP** (or similar local server stack)
   - Apache Web Server
   - MySQL Database Server
   - PHP 7.4 or higher
   - **PHP Extensions Required**:
     - GD (for image processing)
     - OpenSSL (for secure connections)
     - PDO_MySQL (for database access)
     - cURL (for external API calls)
     - mbstring (for string handling)
     - intl (for internationalization)

2. **Web Browser**
   - Chrome, Firefox, Safari, or Edge (latest version recommended)
   - JavaScript must be enabled

3. **Email Configuration**
   - SMTP server access for email notifications
   - Valid email account for system messages

4. **File System Access**
   - Ability to copy files to web server directory
   - Write permissions for uploads and cache directories

## Installation Steps

### Step 1: Download and Extract

1. Download the Library Management System files
2. Extract the files to your XAMPP `htdocs` folder
3. Ensure the folder structure looks like this:
   ```
   xampp/htdocs/library_system/
   ├── index.php
   ├── setup.php
   ├── admin/
   ├── student/
   ├── includes/
   ├── assets/
   ├── database/
   └── ...
   ```

### Step 2: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Ensure both services are running (green status)

### Step 3: Database Configuration

1. Open `includes/config.php` in a text editor
2. Verify the database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'library_management');
   
   // Email Configuration (Update these in Admin Panel after installation)
   define('MAIL_HOST', 'smtp.example.com');
   define('MAIL_USERNAME', 'noreply@yourdomain.com');
   define('MAIL_PASSWORD', 'your_email_password');
   define('MAIL_FROM', 'library@yourdomain.com');
   define('MAIL_FROM_NAME', 'Library Management System');
   ```
3. If you have a different MySQL password, update `DB_PASSWORD`
4. For production, create a dedicated database user with appropriate privileges

### Step 4: Run Setup Scripts

1. Open your web browser
2. Navigate to: `http://localhost/library_system/setup.php`
3. Follow the setup wizard:
   - **Step 1**: Create Database
   - **Step 2**: Import Schema
   - **Step 3**: Import Sample Data
   - **Step 4**: Complete Setup

4. **Run Book Request System Setup**
   - After initial setup, log in as admin
   - Navigate to: `http://localhost/library_system/setup_request_system.php`
   - This will create additional tables for the book request system
   - Verify successful creation of all required tables

### Step 5: Access the System

1. After setup completion, you'll be redirected to the main page
2. Access the system at: `http://localhost/library_system/`

## Default Login Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin123`
- **Access**: Full administrative privileges

### Student Access (Sample Users)
- **Username**: `student1`
- **Password**: `password123`

## Manual Database Setup (Alternative)

If the setup script doesn't work, you can manually set up the database:

1. **Create Database**:
   ```sql
   CREATE DATABASE library_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE library_management;
   ```

2. **Import Schema**:
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Select the `library_management` database
   - Go to Import tab
   - Choose `database/library_db.sql`
   - Click Import

3. **Import Sample Data**:
   - In the same database
   - Import the sample data file:
     ```bash
     mysql -u root -p library_management < database/sample_data_clean.sql
     ```

4. **Set Up Request System Tables**:
   - Log in to the system as admin
   - Navigate to `http://localhost/library_system/setup_request_system.php`
   - Or manually import the request system schema:
     ```sql
     -- Book Requests Table
     CREATE TABLE IF NOT EXISTS `book_requests` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `student_id` int(11) NOT NULL,
       `book_id` int(11) NOT NULL,
       `request_date` datetime NOT NULL,
       `status` enum('pending','approved','rejected','fulfilled') NOT NULL DEFAULT 'pending',
       `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
       `duration_days` int(11) DEFAULT 14,
       `notes` text DEFAULT NULL,
       `admin_notes` text DEFAULT NULL,
       `admin_id` int(11) DEFAULT NULL,
       `processed_date` datetime DEFAULT NULL,
       PRIMARY KEY (`id`),
       KEY `student_id` (`student_id`),
       KEY `book_id` (`book_id`),
       KEY `admin_id` (`admin_id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     
     -- Messages Table
     CREATE TABLE IF NOT EXISTS `messages` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `conversation_id` varchar(100) NOT NULL,
       `sender_id` int(11) NOT NULL,
       `recipient_id` int(11) NOT NULL,
       `message` text NOT NULL,
       `is_read` tinyint(1) NOT NULL DEFAULT 0,
       `created_at` datetime NOT NULL DEFAULT current_timestamp(),
       PRIMARY KEY (`id`),
       KEY `conversation_id` (`conversation_id`),
       KEY `sender_id` (`sender_id`),
       KEY `recipient_id` (`recipient_id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     
     -- Notifications Table
     CREATE TABLE IF NOT EXISTS `notifications` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `user_id` int(11) NOT NULL,
       `title` varchar(255) NOT NULL,
       `message` text NOT NULL,
       `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
       `is_read` tinyint(1) NOT NULL DEFAULT 0,
       `created_at` datetime NOT NULL DEFAULT current_timestamp(),
       `link` varchar(255) DEFAULT NULL,
       PRIMARY KEY (`id`),
       KEY `user_id` (`user_id`),
       KEY `is_read` (`is_read`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     
     -- Email Logs Table
     CREATE TABLE IF NOT EXISTS `email_logs` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `recipient` varchar(255) NOT NULL,
       `subject` varchar(255) NOT NULL,
       `message` text DEFAULT NULL,
       `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
       `error_message` text DEFAULT NULL,
       `created_at` datetime NOT NULL DEFAULT current_timestamp(),
       `sent_at` datetime DEFAULT NULL,
       PRIMARY KEY (`id`),
       KEY `recipient` (`recipient`),
       KEY `status` (`status`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
     
     -- Add foreign key constraints
     ALTER TABLE `book_requests`
       ADD CONSTRAINT `book_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
       ADD CONSTRAINT `book_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
       ADD CONSTRAINT `book_requests_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
     
     ALTER TABLE `messages`
       ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
       ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
     
     ALTER TABLE `notifications`
       ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
     ```

## Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check if MySQL is running
   - Verify database credentials in `config.php`
   - Ensure database exists

2. **"Permission denied"**
   - Check file permissions
   - Ensure web server can read/write to the directory

3. **"Setup script not found"**
   - Verify file path: `http://localhost/library_system/setup.php`
   - Check if setup.php exists in the root directory

4. **"Tables already exist"**
   - Drop existing database and recreate
   - Or skip schema import if tables exist

### Error Logs

Check XAMPP error logs:
- Apache: `xampp/apache/logs/error.log`
- MySQL: `xampp/mysql/data/mysql_error.log`

## Security Considerations

1. **Initial Security Setup**
   - Change all default passwords immediately after installation
   - Update admin email and contact information
   - Configure HTTPS for secure connections

2. **Database Security**
   - Use strong, unique passwords for database users
   - Create separate database users with minimal required privileges
   - Regularly backup the database
   - Enable database logging

3. **File System Security**
   - Set directory permissions:
     - 755 for directories
     - 644 for files
     - 775 for uploads directory
   - Remove or secure sensitive files:
     - Delete setup.php after installation
     - Secure .htaccess files
     - Protect configuration files

4. **Email Security**
   - Use SMTP with authentication
   - Configure SPF, DKIM, and DMARC records
   - Set up email rate limiting
   - Monitor email logs for abuse

5. **Request System Security**
   - Implement rate limiting for requests
   - Validate all user inputs
   - Sanitize output to prevent XSS
   - Use prepared statements for all database queries

6. **Regular Maintenance**
   - Keep PHP and all dependencies updated
   - Monitor system logs
   - Regularly review user accounts and permissions
   - Implement security headers

## Post-Installation

After successful installation:

1. **Initial Configuration**
   - Log in to the admin panel
   - Navigate to System Settings
   - Configure the following:
     - Library name and contact information
     - Loan periods and fine rates
     - Email notification settings
     - Request system preferences

2. **User Management**
   - Change the default admin password
   - Create librarian accounts
   - Import or create student/faculty accounts
   - Set up user roles and permissions

3. **Content Setup**
   - Import book catalog or add books manually
   - Set up book categories and subjects
   - Configure call numbers and locations
   - Add book covers or enable auto-generation

4. **Request System Setup**
   - Configure request settings:
     - Maximum concurrent requests per user
     - Default loan durations
     - Priority levels
     - Notification templates
   - Test the request workflow
   - Train staff on request processing

5. **Security Hardening**
   - Remove setup files:
     - Delete `setup.php`
     - Delete `setup_request_system.php`
     - Delete `setup_completed.txt`
   - Set up regular backups
   - Configure server security headers
   - Enable security modules (e.g., mod_security)

6. **Testing**
   - Test all user roles and permissions
   - Verify email notifications
   - Test the request workflow
   - Check mobile responsiveness
   - Verify all forms and validations

7. **Documentation**
   - Document system configuration
   - Create user guides for staff and students
   - Document emergency procedures
   - Set up a maintenance schedule

## Support

For technical support or questions:
- **Developer**: Mohammad Muqsit Raja (BCA22739)
- **Institution**: University of Mysore
- **Year**: 2025

---

**Note**: This system is developed for academic purposes as part of the BCA curriculum at the University of Mysore. 