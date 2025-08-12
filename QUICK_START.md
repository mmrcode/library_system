# Library Management System - Quick Start Guide

**Project by:** Mohammad Muqsit Raja  
**Contact:** mmrcode1@gmail.com  
**GitHub:** [github.com/mmrcode](https://github.com/mmrcode)

## 🚀 Quick Access Links

### Main Application
- **Homepage**: http://localhost/library_system/
- **Setup Page**: http://localhost/library_system/setup.php
- **Admin Login**: http://localhost/library_system/admin/dashboard.php
- **Student Login**: http://localhost/library_system/student/dashboard.php
 - **Tests Runner**: http://localhost/library_system/tests/

### Database Management
- **phpMyAdmin**: http://localhost/phpmyadmin
- **Database Name**: `library_management`

## 📋 Default Login Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin123`
- **Features**: Full system administration

### Librarian Access
- **Username**: `librarian1`
- **Password**: `password123`
- **Features**: Book management, request processing, user communication

### Student Access (Sample Users)
- **Username**: `student1` to `student10`
- **Password**: `password123`
- **Features**: 
  - Book browsing and searching
  - Book request submission
  - In-app messaging with librarians
  - Request status tracking
  - Issue history and profile management

### Faculty Access
- **Username**: `faculty1` to `faculty4`
- **Password**: `password123`
- **Features**: Extended borrowing privileges, priority request handling

## 🛠️ Installation Steps

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Run Setup**
   - Visit: http://localhost/library_system/setup.php
   - Follow the 4-step wizard
   - **Important A**: After initial setup, run: http://localhost/library_system/setup_request_system.php
     - Sets up the Book Request + Messaging system tables (book_requests, messages, notifications, email_logs, system_settings)
     - Must be logged in as admin
   - **Important B**: Also run: http://localhost/library_system/setup_fine_system.php
     - Sets up the Fine Management tables (fines, fine_transactions)
     - Configures defaults used by reports

3. **Verify Email Configuration**
   - Navigate to Admin Panel > System Settings
   - Configure SMTP settings for email notifications
   - Test the email functionality

4. **Access System**
   - Visit: http://localhost/library_system/
   - Login with admin credentials
   - Open the test runner (optional): http://localhost/library_system/tests/

## 📁 Project Structure

```
library_system/
├── index.php              # Main landing page
├── setup.php              # Installation wizard
├── admin/                 # Admin module
│   ├── dashboard.php      # Admin dashboard
│   ├── books.php          # Book management
│   ├── users.php          # User management
│   ├── issues.php         # Issue/return management
│   └── reports/           # Report generation
├── student/               # Student module
│   ├── dashboard.php      # Student dashboard
│   ├── search_books.php   # Book search
│   └── my_books.php       # Issued books
├── includes/              # Core files
│   ├── config.php         # Database configuration
│   ├── database.php       # Database connection
│   └── functions.php      # Helper functions
├── assets/                # Static files
│   └── css/style.css      # Custom styling
└── database/              # Database files
    ├── library_db.sql     # Database schema
    └── sample_data.sql    # Sample data
```

## 🔧 Key Features

### Admin & Librarian Features
- ✅ Book Management (Add/Edit/Delete)
- ✅ User Management (Students/Faculty)
- ✅ Request Processing System
  - View and manage book requests
  - Set request priorities
  - Approve/Reject requests
  - Track request status
- ✅ Communication Tools
  - In-app messaging with students
  - Email notifications
  - Broadcast announcements
- ✅ Issue & Return Processing
- ✅ Fine Management
  - Payment/waiver history, pending fines display
  - Admin fine dashboard and detailed charts
- ✅ Reports & Analytics
- ✅ Category Management
- ✅ System Settings

### Student & Faculty Features
- ✅ Book Search & Browse
- ✅ Book Request System
  - Submit book requests
  - Set priority levels
  - Specify needed duration
  - Add special notes
- ✅ Communication Center
  - Message librarians directly
  - Receive notifications
  - View message history
- ✅ Request Tracking
  - View request status
  - Check approval status
  - Receive updates
- ✅ View Issued Books
- ✅ Check Due Dates
- ✅ View Fine Details
- ✅ Profile Management
- ✅ Transaction History

## 🎨 UI/UX Features

- **Modern Design**: Bootstrap 5 with custom CSS
- **Responsive**: Works on all devices
- **Glass Morphism**: Modern visual effects
- **Dark Sidebar**: Professional admin interface
- **Interactive Elements**: Hover effects and animations
- **Accessibility**: Screen reader friendly

## 📊 Database Schema

### Main Tables
- `users` - Admin and student accounts
- `books` - Book inventory
- `categories` - Book categories
- `book_issues` - Issue/return tracking
- `fines` - Fine management
 - `fine_transactions` - Fine payments/waivers
- `system_settings` - Configuration
- `activity_logs` - System logs

## 🔒 Security Features

- **Password Hashing**: Bcrypt encryption
- **Session Management**: Secure session handling
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Form token validation
- **Role-based Access**: Admin/Student separation

## 📈 Sample Data Included

- **6 Book Categories**: Computer Science, Mathematics, Physics, Literature, History, Business
- **40+ Sample Books**: Programming, AI/ML, Mathematics, Physics, Literature, Economics, and more
- **15+ Sample Users**: 
  - 1 Admin
  - 10 Students
  - 4 Faculty
  - 1 Librarian
- **20+ Sample Issues**: Active, returned, and overdue books with realistic transaction history
- **Sample Book Requests**:
  - Pending approval
  - Approved and fulfilled
  - Rejected with reasons
  - Various priority levels
- **Sample Messages**:
  - Student inquiries
  - Librarian responses
  - System notifications
- **Fine Records**: Sample overdue fines with different statuses
- **Activity Logs**: Comprehensive system activity tracking
- **System Settings**: Complete library configuration including email and request settings

## 🚨 Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check if MySQL is running
   - Verify credentials in `includes/config.php`
   - Ensure all required database tables exist

2. **"Setup page not found"**
   - Ensure files are in `xampp/htdocs/library_system/`
   - Check Apache is running
   - Verify .htaccess rules if using URL rewriting

3. **"Permission denied"**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server can access files
   - Verify uploads directory has write permissions (777 recommended for uploads)

4. **"Email not sending"**
   - Check SMTP settings in admin panel
   - Verify PHP mail() function is working
   - Check spam/junk folders
   - Review `email_logs` table for error messages

5. **Book request issues**
   - Ensure `setup_request_system.php` was run
   - Check database tables: book_requests, messages, notifications
   - Verify user roles and permissions
6. **Fine system not showing data**
   - Ensure `setup_fine_system.php` was run
   - Try adding sample issues/returns to generate fines
   - Check `fines` and `fine_transactions` tables

### Error Logs
- **Apache**: `xampp/apache/logs/error.log`
- **MySQL**: `xampp/mysql/data/mysql_error.log`

## 📞 Support Information

- **Developer**: Mohammad Muqsit Raja
- **Registration**: BCA22739
- **University**: University of Mysore
- **Year**: 2025
- **Technology**: PHP, MySQL, Bootstrap 5

## 🔄 Post-Installation

1. **Remove Setup Files**
   - Delete `setup.php` for security
   - Delete `setup_completed.txt`
   - Optionally remove `setup_request_system.php` and `setup_fine_system.php` (keep backups for re-install)

2. **Change Default Passwords**
   - Update admin password
   - Change student passwords

3. **Configure System**
   - Update library information
   - Set working hours
   - Configure fine rates

4. **Add Content**
   - Import book catalog
   - Create student accounts
   - Set up categories

---

**🎓 Academic Project**: This system is developed as part of the Bachelor of Computer Applications (BCA) curriculum at the University of Mysore.

**📝 License**: Academic use only - University of Mysore 2025 