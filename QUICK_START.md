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

### Database Management
- **phpMyAdmin**: http://localhost/phpmyadmin
- **Database Name**: `library_management`

## 📋 Default Login Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin123`
- **Features**: Full system administration

### Student Access (Sample Users)
- **Username**: `student1` to `student10` | **Password**: `password123`
- **Faculty**: `faculty1` to `faculty4` | **Password**: `password123`
- **Librarian**: `librarian1` | **Password**: `password123`
- **Features**: Book browsing, issue history, profile management

## 🛠️ Installation Steps

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Run Setup**
   - Visit: http://localhost/library_system/setup.php
   - Follow the 4-step wizard

3. **Access System**
   - Visit: http://localhost/library_system/
   - Login with admin credentials

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

### Admin Features
- ✅ Book Management (Add/Edit/Delete)
- ✅ User Management (Students/Faculty)
- ✅ Issue & Return Processing
- ✅ Fine Management
- ✅ Reports & Analytics
- ✅ Category Management
- ✅ System Settings

### Student Features
- ✅ Book Search & Browse
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
- **15+ Sample Users**: 1 Admin, 10 Students, 4 Faculty, 1 Librarian
- **20+ Sample Issues**: Active, returned, and overdue books with realistic transaction history
- **Fine Records**: Sample overdue fines with different statuses
- **Activity Logs**: Comprehensive system activity tracking
- **System Settings**: Complete library configuration

## 🚨 Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check if MySQL is running
   - Verify credentials in `includes/config.php`

2. **"Setup page not found"**
   - Ensure files are in `xampp/htdocs/library_system/`
   - Check Apache is running

3. **"Permission denied"**
   - Check file permissions
   - Ensure web server can access files

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