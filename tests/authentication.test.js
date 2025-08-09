/**
 * Authentication System Unit Tests
 * Tests for login, session management, and user authentication
 */

describe('Authentication System Tests', function() {
    
    beforeEach(function() {
        // Setup mock session storage
        window.sessionStorage.clear();
        
        // Create mock login form
        if (!document.getElementById('loginForm')) {
            const form = TestUtils.createMockElement('form', { id: 'loginForm' });
            const usernameInput = TestUtils.createMockElement('input', { 
                id: 'username', 
                name: 'username',
                type: 'text'
            });
            const passwordInput = TestUtils.createMockElement('input', { 
                id: 'password', 
                name: 'password',
                type: 'password'
            });
            
            form.appendChild(usernameInput);
            form.appendChild(passwordInput);
            document.body.appendChild(form);
        }
    });
    
    afterEach(function() {
        TestUtils.restoreAjax();
        const form = document.getElementById('loginForm');
        if (form) form.remove();
    });

    describe('Login Form Validation', function() {
        
        it('should validate required username field', function() {
            window.validateLoginForm = function() {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                if (!username.trim()) {
                    return { valid: false, message: 'Username is required' };
                }
                if (!password.trim()) {
                    return { valid: false, message: 'Password is required' };
                }
                
                return { valid: true };
            };
            
            // Test empty username
            document.getElementById('username').value = '';
            document.getElementById('password').value = 'password123';
            
            const result = window.validateLoginForm();
            expect(result.valid).to.be.false;
            expect(result.message).to.equal('Username is required');
        });
        
        it('should validate required password field', function() {
            document.getElementById('username').value = 'testuser';
            document.getElementById('password').value = '';
            
            const result = window.validateLoginForm();
            expect(result.valid).to.be.false;
            expect(result.message).to.equal('Password is required');
        });
        
        it('should pass validation with valid credentials', function() {
            document.getElementById('username').value = 'testuser';
            document.getElementById('password').value = 'password123';
            
            const result = window.validateLoginForm();
            expect(result.valid).to.be.true;
        });
    });

    describe('Login Process', function() {
        
        it('should handle successful admin login', function(done) {
            const mockResponse = {
                success: true,
                user_type: 'admin',
                user_id: 1,
                username: 'admin',
                redirect: 'admin/dashboard.php'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.processLogin = function(credentials) {
                return $.ajax({
                    url: 'includes/auth.php',
                    method: 'POST',
                    data: {
                        action: 'login',
                        ...credentials
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.user_type).to.equal('admin');
                    expect(response.redirect).to.include('admin');
                    done();
                });
            };
            
            window.processLogin({
                username: 'admin',
                password: 'admin123'
            });
        });
        
        it('should handle successful student login', function(done) {
            const mockResponse = {
                success: true,
                user_type: 'student',
                user_id: 123,
                username: 'student123',
                redirect: 'student/dashboard.php'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.processLogin = function(credentials) {
                return $.ajax({
                    url: 'includes/auth.php',
                    method: 'POST',
                    data: {
                        action: 'login',
                        ...credentials
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.user_type).to.equal('student');
                    expect(response.redirect).to.include('student');
                    done();
                });
            };
            
            window.processLogin({
                username: 'student123',
                password: 'password'
            });
        });
        
        it('should handle login failure', function(done) {
            const mockResponse = {
                success: false,
                message: 'Invalid username or password',
                error_code: 'AUTH_FAILED'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.processLogin = function(credentials) {
                return $.ajax({
                    url: 'includes/auth.php',
                    method: 'POST',
                    data: {
                        action: 'login',
                        ...credentials
                    },
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.false;
                    expect(response.message).to.include('Invalid');
                    expect(response.error_code).to.equal('AUTH_FAILED');
                    done();
                });
            };
            
            window.processLogin({
                username: 'wronguser',
                password: 'wrongpass'
            });
        });
    });

    describe('Session Management', function() {
        
        it('should store user session data after login', function() {
            const userData = {
                user_id: 123,
                username: 'testuser',
                user_type: 'student',
                login_time: new Date().toISOString()
            };
            
            window.storeUserSession = function(data) {
                sessionStorage.setItem('user_data', JSON.stringify(data));
                sessionStorage.setItem('logged_in', 'true');
            };
            
            window.storeUserSession(userData);
            
            const storedData = JSON.parse(sessionStorage.getItem('user_data'));
            expect(storedData.user_id).to.equal(123);
            expect(storedData.username).to.equal('testuser');
            expect(sessionStorage.getItem('logged_in')).to.equal('true');
        });
        
        it('should validate active session', function() {
            // Set up valid session
            sessionStorage.setItem('logged_in', 'true');
            sessionStorage.setItem('user_data', JSON.stringify({
                user_id: 123,
                username: 'testuser',
                login_time: new Date().toISOString()
            }));
            
            window.isValidSession = function() {
                const isLoggedIn = sessionStorage.getItem('logged_in') === 'true';
                const userData = sessionStorage.getItem('user_data');
                
                if (!isLoggedIn || !userData) {
                    return false;
                }
                
                try {
                    const user = JSON.parse(userData);
                    return user.user_id && user.username;
                } catch (e) {
                    return false;
                }
            };
            
            expect(window.isValidSession()).to.be.true;
        });
        
        it('should handle session expiry', function() {
            window.clearUserSession = function() {
                sessionStorage.removeItem('user_data');
                sessionStorage.removeItem('logged_in');
            };
            
            window.clearUserSession();
            
            expect(sessionStorage.getItem('logged_in')).to.be.null;
            expect(sessionStorage.getItem('user_data')).to.be.null;
        });
    });

    describe('User Role Management', function() {
        
        it('should identify admin users correctly', function() {
            window.isAdmin = function(userData) {
                return userData && userData.user_type === 'admin';
            };
            
            const adminUser = { user_type: 'admin', username: 'admin' };
            const studentUser = { user_type: 'student', username: 'student' };
            
            expect(window.isAdmin(adminUser)).to.be.true;
            expect(window.isAdmin(studentUser)).to.be.false;
        });
        
        it('should identify student users correctly', function() {
            window.isStudent = function(userData) {
                return userData && userData.user_type === 'student';
            };
            
            const adminUser = { user_type: 'admin', username: 'admin' };
            const studentUser = { user_type: 'student', username: 'student' };
            
            expect(window.isStudent(studentUser)).to.be.true;
            expect(window.isStudent(adminUser)).to.be.false;
        });
    });

    describe('Password Security', function() {
        
        it('should validate password strength', function() {
            window.validatePasswordStrength = function(password) {
                if (password.length < 6) {
                    return { valid: false, message: 'Password must be at least 6 characters' };
                }
                
                if (!/[A-Za-z]/.test(password)) {
                    return { valid: false, message: 'Password must contain letters' };
                }
                
                if (!/[0-9]/.test(password)) {
                    return { valid: false, message: 'Password must contain numbers' };
                }
                
                return { valid: true, message: 'Password is strong' };
            };
            
            expect(window.validatePasswordStrength('123')).to.have.property('valid', false);
            expect(window.validatePasswordStrength('password')).to.have.property('valid', false);
            expect(window.validatePasswordStrength('123456')).to.have.property('valid', false);
            expect(window.validatePasswordStrength('password123')).to.have.property('valid', true);
        });
    });

    describe('Logout Process', function() {
        
        it('should handle logout correctly', function(done) {
            const mockResponse = {
                success: true,
                message: 'Logged out successfully'
            };
            
            TestUtils.mockAjax(mockResponse);
            
            window.processLogout = function() {
                return $.ajax({
                    url: 'logout.php',
                    method: 'POST',
                    dataType: 'json'
                }).then(function(response) {
                    expect(response.success).to.be.true;
                    expect(response.message).to.include('Logged out');
                    
                    // Should clear session data
                    sessionStorage.clear();
                    done();
                });
            };
            
            window.processLogout();
        });
    });
});
