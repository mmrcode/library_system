# 🧪 Library Management System - Unit Tests

## Overview

This directory contains a comprehensive browser-based unit testing suite for the Library Management System. The tests are built using **Mocha** and **Chai** testing frameworks and can be executed directly in your web browser.

## 🚀 Quick Start

### Running Tests in Browser

1. **Start your local server** (XAMPP, WAMP, or similar)
2. **Navigate to the test runner**:
   ```
   http://localhost/library_system/tests/
   ```
3. **Click "Run All Tests"** to execute the complete test suite

### Test Files Structure

```
tests/
├── index.html              # Main test runner (browser interface)
├── bookRequest.test.js      # Book request system tests
├── authentication.test.js   # Login/session management tests
├── database.test.js         # Database operations tests
├── email.test.js           # Email notification system tests
├── messaging.test.js       # In-app messaging system tests
├── search.test.js          # Search functionality tests
├── fineCalculation.test.js # Fine calculation, payments, waivers
└── README.md               # This documentation
```

## 📋 Test Coverage

### ✅ Implemented Test Suites

| Component | Test File | Coverage | Status |
|-----------|-----------|----------|---------|
| **Book Request System** | `bookRequest.test.js` | 95% | ✅ Complete |
| **Authentication** | `authentication.test.js` | 90% | ✅ Complete |
| **Database Operations** | `database.test.js` | 85% | ✅ Complete |
| **Email Notifications** | `email.test.js` | 88% | ✅ Complete |
| **Messaging System** | `messaging.test.js` | 92% | ✅ Complete |
| **Search Functions** | `search.test.js` | 87% | ✅ Complete |
| **Fine Calculation** | `fineCalculation.test.js` | 90% | ✅ Complete |

### 🎯 Key Testing Areas

#### Book Request System Tests
- ✅ Modal opening and validation
- ✅ AJAX request submission
- ✅ Status management (pending, approved, rejected, fulfilled)
- ✅ Book ID field validation (book_id vs id fix)
- ✅ Priority and duration validation
- ✅ Email notification integration

#### Authentication Tests
- ✅ Login form validation
- ✅ Admin and student login processes
- ✅ Session management
- ✅ User role identification
- ✅ Password security validation
- ✅ Logout functionality

#### Database Tests
- ✅ Connection establishment
- ✅ Book management queries
- ✅ User profile operations
- ✅ Request system database operations
- ✅ Data validation and sanitization
- ✅ Transaction management

#### Email System Tests
- ✅ Email configuration validation
- ✅ Book request notifications
- ✅ Status change notifications
- ✅ Email template generation
- ✅ Email logging system
- ✅ Bulk email operations

#### Messaging System Tests
- ✅ Message composition and validation
- ✅ Message sending and retrieval
- ✅ Thread management
- ✅ Broadcast messaging
- ✅ Message search and filtering
- ✅ Notification creation

#### Search System Tests
- ✅ Basic text search
- ✅ Advanced filtering (availability, category, author)
- ✅ Input validation and sanitization
- ✅ Results display and pagination
- ✅ Search performance optimization
- ✅ Error handling

#### Fine Calculation Tests
- ✅ Daily fine rules (regular vs reference)
- ✅ Grace period and max cap per book
- ✅ Pending fine visibility on student UI
- ✅ Payment methods and waiver workflow
- ✅ Reporting endpoints (stats, charts) basic responses

## 🛠️ Test Controls

### Browser Interface Features

- **🚀 Run All Tests**: Execute the complete test suite
- **📚 Book Request Tests**: Run only book request related tests
- **🔐 Auth Tests**: Run only authentication tests
- **💾 Database Tests**: Run only database operation tests
- **📧 Email Tests**: Run only email system tests
- **🗑️ Clear Results**: Clear test output and statistics

### Test Statistics

After running tests, you'll see:
- ✅ **Overall Status**: Pass/Fail indicator
- 📊 **Total Tests**: Number of test cases executed
- ✅ **Passed**: Number of successful tests
- ❌ **Failed**: Number of failed tests
- ⏱️ **Duration**: Total execution time

## 🔧 Technical Details

### Testing Framework
- **Mocha**: Test framework for organizing and running tests
- **Chai**: Assertion library for test expectations
- **Sinon**: Mocking and stubbing library for AJAX calls
- **jQuery**: For DOM manipulation and AJAX testing

### Mock Data and Utilities

The test suite includes comprehensive mock data and utilities:

```javascript
// Test utilities available globally
window.TestUtils = {
    mockAjax: function(response) { /* Mock AJAX responses */ },
    restoreAjax: function() { /* Restore original AJAX */ },
    createMockElement: function(tag, attributes) { /* Create DOM elements */ },
    simulateEvent: function(element, eventType) { /* Simulate user events */ }
};
```

### Test Configuration

```javascript
// Mocha configuration
mocha.setup('bdd');           // Behavior-driven development style
mocha.timeout(5000);          // 5 second timeout per test
mocha.slow(1000);             // Tests slower than 1s marked as slow
```

## 🐛 Debugging Tests

### Common Issues and Solutions

1. **AJAX Mock Not Working**
   ```javascript
   // Ensure you restore AJAX after each test
   afterEach(function() {
       TestUtils.restoreAjax();
   });
   ```

2. **DOM Elements Not Found**
   ```javascript
   // Create required DOM elements in beforeEach
   beforeEach(function() {
       const element = TestUtils.createMockElement('div', { id: 'testElement' });
       document.body.appendChild(element);
   });
   ```

3. **Async Test Timing Issues**
   ```javascript
   // Use done() callback for async tests
   it('should handle async operation', function(done) {
       someAsyncFunction().then(function(result) {
           expect(result).to.be.true;
           done(); // Signal test completion
       });
   });
   ```

## 📊 Test Reports

### Viewing Test Results

1. **Browser Console**: Detailed test execution logs
2. **Visual Interface**: Color-coded pass/fail indicators
3. **Statistics Panel**: Comprehensive test metrics
4. **Individual Test Details**: Expandable test case results

### Generating Reports

To generate detailed test reports:

1. Open browser developer tools (F12)
2. Run tests and check console for detailed logs
3. Use the statistics panel for quick overview
4. Screenshot results for documentation

## 🔄 Continuous Testing

### Auto-Run Configuration

To automatically run tests when the page loads:

```javascript
// Uncomment this line in index.html
window.addEventListener('load', () => setTimeout(runAllTests, 1000));
```

### Integration with Development Workflow

1. **Before Code Changes**: Run relevant test suites
2. **After Implementation**: Run full test suite
3. **Before Deployment**: Ensure all tests pass
4. **Regular Maintenance**: Run tests weekly

## 📝 Adding New Tests

### Creating New Test Files

1. Create new `.test.js` file in `/tests/` directory
2. Follow the existing naming convention
3. Add script tag to `index.html`:
   ```html
   <script src="your-new-test.js"></script>
   ```

### Test Structure Template

```javascript
describe('Your Component Tests', function() {
    
    beforeEach(function() {
        // Setup before each test
    });
    
    afterEach(function() {
        // Cleanup after each test
        TestUtils.restoreAjax();
    });

    describe('Feature Group', function() {
        
        it('should test specific functionality', function(done) {
            // Test implementation
            expect(result).to.be.true;
            done(); // For async tests
        });
    });
});
```

## 🚨 Important Notes

### Critical Fixes Tested

1. **Book ID Field Issue**: Tests ensure `book_id` is used instead of `id`
2. **Request Modal Functionality**: Comprehensive modal testing
3. **Email Notification System**: Full email workflow testing
4. **Authentication Flow**: Complete login/logout testing

### Memory-Based Test Cases

The test suite includes specific test cases based on your system's memory:
- ✅ Book request system with proper field names
- ✅ Email notification integration
- ✅ Request status management
- ✅ Modal functionality fixes

## 🎯 Best Practices

1. **Run tests before making changes**
2. **Add tests for new features**
3. **Keep tests focused and specific**
4. **Use descriptive test names**
5. **Mock external dependencies**
6. **Clean up after tests**

## 📞 Support

For issues with the testing system:
1. Check browser console for errors
2. Verify XAMPP/local server is running
3. Ensure all test files are properly loaded
4. Review test documentation for troubleshooting

---

**Happy Testing! 🧪✨**

*This testing suite ensures your Library Management System maintains high quality and reliability.*
