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
   - **PHP GD Extension** (recommended for book cover generation)

2. **Web Browser**
   - Chrome, Firefox, Safari, or Edge

3. **File System Access**
   - Ability to copy files to web server directory
   - Write permissions for uploads folder

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
   ```
3. If you have a different MySQL password, update `DB_PASSWORD`

### Step 4: Run Setup Script

1. Open your web browser
2. Navigate to: `http://localhost/library_system/setup.php`
3. Follow the setup wizard:
   - **Step 1**: Create Database
   - **Step 2**: Import Schema
   - **Step 3**: Import Sample Data
   - **Step 4**: Complete Setup

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
   CREATE DATABASE library_management;
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
   - Run the following command in your terminal/command prompt:
   ```bash
   mysql -u root -p library_management < database/sample_data_clean.sql
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

1. **Change Default Passwords**
   - Update admin password after first login
   - Change default student passwords

2. **Database Security**
   - Use strong MySQL passwords in production
   - Restrict database user privileges

3. **File Permissions**
   - Set appropriate file permissions
   - Remove setup.php after installation

## Post-Installation

After successful installation:

1. **Remove Setup Files**:
   - Delete `setup.php` (for security)
   - Delete `setup_completed.txt`

2. **Configure Settings**:
   - Log in as admin
   - Update library information
   - Configure system settings

3. **Add Users**:
   - Create student accounts
   - Set up faculty accounts if needed

4. **Add Books**:
   - Import book catalog
   - Set up categories

## Support

For technical support or questions:
- **Developer**: Mohammad Muqsit Raja (BCA22739)
- **Institution**: University of Mysore
- **Year**: 2025

---

**Note**: This system is developed for academic purposes as part of the BCA curriculum at the University of Mysore. 