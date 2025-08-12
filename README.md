# Library Management System (PHP + MySQL)

**Project by:** Mohammad Muqsit Raja  
**Contact:** mmrcode1@gmail.com  
**GitHub:** [github.com/mmrcode](https://github.com/mmrcode)

## Overview

This is a full-stack Library Management System with separate Admin and Student modules. It includes book inventory, issuing/returning, request queue + messaging, fine calculation/reporting, and a browser-based test runner. Recently, the codebase was “humanized” with realistic student-style comments across key files without changing functionality (for academic submission vibe, but still clean and maintainable).

## 🚀 Modules & Major Features

- **Admin Module**
  - Book management, categories, users
  - Issue/Return processing
  - Fine management and reporting (Chart.js dashboards)
  - Request processing (approve/reject/fulfill), bulk actions
  - Messaging center and broadcast

- **Student Module**
  - Search/browse books with filters and pagination
  - Request books (priority, duration, notes) with status tracking
  - In-app messaging with librarians, notifications
  - My Books (active/due soon/overdue/returned), fine visibility

- **Systems**
  - Email notifications (PHPMailer) + email logs
  - Request queue + notifications + settings
  - Fine calculation engine (grace period, caps, categories) + transactions
  - Test runner with Mocha/Chai (browser-based)

## 📁 Quick Structure

```
library_system/
├── admin/                 # Admin UI (books, users, issues/returns, reports)
├── student/               # Student UI (search, my books, requests, messages)
├── includes/              # Core libs (auth, db, email, requests, fines)
├── admin/api/             # Admin-facing APIs
├── tests/                 # Browser-based tests (Mocha + Chai)
├── assets/                # CSS/JS
├── setup.php              # Base setup wizard
├── setup_request_system.php  # Creates request/messaging tables
├── setup_fine_system.php     # Creates fine/transactions tables
└── index.php
```

## 🎨 UI/UX Highlights

### 1. **Dashboard Cards**
- **Gradient Backgrounds**: Modern gradient designs
- **Hover Effects**: Smooth animations on interaction
- **Status Indicators**: Color-coded status badges
- **Responsive Layout**: Adapts to different screen sizes

### 2. **Timeline Design**
- **Activity Timeline**: Visual representation of recent activity
- **Custom Icons**: FontAwesome icons for better UX
- **Smooth Animations**: Fade-in effects for content

### 3. **Search Interface**
- **Advanced Filters**: Category, author, and availability filters
- **Real-time Results**: Instant search results
- **Pagination**: Efficient data loading
- **Book Cards**: Attractive book display cards

## 🔧 Technical Notes

### 1. **Performance Optimizations**
- **AJAX Loading**: Asynchronous data loading
- **Caching**: Efficient data caching
- **Optimized Queries**: Improved database queries
- **Lazy Loading**: Progressive content loading

### 2. **Security Enhancements**
- **Input Validation**: Comprehensive input sanitization
- **Session Management**: Secure session handling
- **Access Control**: Proper authentication checks
- **CSRF Protection**: Cross-site request forgery protection

### 3. **Code Organization**
- **Modular Structure**: Separated concerns
- **Reusable Functions**: Common functionality extraction
- **Consistent Naming**: Standardized naming conventions
- **Documentation**: Comprehensive code comments

## 📱 Mobile

### 1. **Responsive Breakpoints**
- **Desktop**: Full feature set with sidebar
- **Tablet**: Optimized layout for medium screens
- **Mobile**: Simplified layout for small screens

### 2. **Touch-Friendly Interface**
- **Large Buttons**: Easy-to-tap interface elements
- **Swipe Gestures**: Mobile-friendly navigation
- **Optimized Forms**: Touch-friendly form inputs

## 🔄 Auto-Refresh

### 1. **Dashboard Updates**
- **Statistics**: Real-time statistics updates
- **Notifications**: Live notification badges
- **Activity Feed**: Recent activity updates
- **Book Status**: Current book status updates

### 2. **Session Management**
- **Auto-Extension**: Automatic session renewal
- **Timeout Warnings**: User-friendly timeout alerts
- **Secure Logout**: Proper session cleanup

## 📊 Statistics

### 1. **Reading Analytics**
- **Total Books**: Complete borrowing history
- **Current Issues**: Active book count
- **Overdue Books**: Overdue book tracking
- **Pending Fines**: Fine calculation and display

### 2. **Personalized Recommendations**
- **Category-Based**: Recommendations based on reading history
- **Popular Books**: Trending book suggestions
- **Availability Status**: Real-time availability checking

## 🎯 User Experience

### 1. **Smart Notifications**
- **Overdue Alerts**: Automatic overdue book notifications
- **Due Soon Warnings**: Books due within 3 days
- **Fine Notifications**: Pending fine alerts
- **System Messages**: Important system notifications

### 2. **Interactive Elements**
- **Hover Effects**: Visual feedback on interaction
- **Loading States**: Progress indicators
- **Error Handling**: User-friendly error messages
- **Success Feedback**: Confirmation messages

## 🛠️ Installation & Setup

1) Start XAMPP (Apache + MySQL)

2) Run base setup wizard:  
   http://localhost/library_system/setup.php

3) Run Book Request system setup:  
   http://localhost/library_system/setup_request_system.php (admin login required)

4) Run Fine system setup:  
   http://localhost/library_system/setup_fine_system.php (admin login required)

5) Configure SMTP in Admin > Settings (for email notifications)

6) Login and verify:  
   Admin: http://localhost/library_system/admin/dashboard.php  
   Student: http://localhost/library_system/student/dashboard.php

## 🔍 Useful Links

- Test Runner: http://localhost/library_system/tests/
- Fine Report (dashboard): `admin/fine_report.php`
- Detailed Fine Report (charts): `admin/reports/fine-calculation-report.html`
- Student Portal Guide: `STUDENT_PORTAL_README.md`
 
## 🚀 Future Enhancements

### 1. **Planned Features**
- **Book Reviews**: Student book rating system
- **Reading Lists**: Personal reading lists
- **Book Reservations**: Reserve unavailable books
- **Digital Library**: E-book integration

### 2. **Technical Improvements**
- **Progressive Web App**: PWA capabilities
- **Offline Support**: Offline functionality
- **Push Notifications**: Real-time notifications
- **Analytics Dashboard**: Advanced reading analytics

## 📝 Changelog

### 2025-08-13
- Humanization pass: added casual student-style comments across `admin/users.php`, `admin/overdue_books.php`, `student/search_books.php`, `student/my_books.php` (no logic changes)
- README/Quick Start updated to cover Request/Messaging and Fine systems, plus test runner

### v2.0
- Restructured dashboards, AJAX functionality, mobile improvements, real-time updates

### v1.0
- Basic portal functionality and standard search/management

## 👨‍💻 Developer Information

**Author**: Mohammad Muqsit Raja  
**Registration**: BCA22739  
**University**: University of Mysore  
**Year**: 2025  

##  Support
mmrcode1@gmail.com

For technical support or feature requests, open an issue or contact the developer.

---

*This LMS includes admin + student portals, request/messaging and fine systems, with a modern UI and comprehensive tests.*
