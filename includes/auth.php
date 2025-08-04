<?php
/**
 * Authentication and Authorization Functions
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            $sql = "SELECT user_id, username, password, full_name, email, user_type, status 
                    FROM users WHERE username = ? AND status = 'active'";
            $user = $this->db->fetchOne($sql, [$username]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Log the login activity
                $this->logActivity($user['user_id'], 'LOGIN', 'users', $user['user_id']);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'redirect' => $this->getRedirectUrl($user['user_type'])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id']);
        }
        
        // Destroy session
        session_destroy();
        
        // Redirect to login page
        header('Location: index.php');
        exit();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if user is student
     */
    public function isStudent() {
        return $this->hasRole('student');
    }
    
    /**
     * Require login
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            $this->logout();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    /**
     * Require student access
     */
    public function requireStudent() {
        $this->requireLogin();
        if (!$this->isStudent()) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $sql = "SELECT user_id, username, full_name, email, user_type, registration_number, 
                       department, year_of_study, phone, address, status, created_at 
                FROM users WHERE user_id = ?";
        return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $sql = "SELECT password FROM users WHERE user_id = ?";
            $user = $this->db->fetchOne($sql, [$userId]);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users', 
                ['password' => $hashedPassword], 
                'user_id = ?', 
                [$userId]
            );
            
            $this->logActivity($userId, 'PASSWORD_CHANGE', 'users', $userId);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to change password'
            ];
        }
    }
    
    /**
     * Register new user (admin only)
     */
    public function registerUser($userData) {
        try {
            // Check if username already exists
            $sql = "SELECT user_id FROM users WHERE username = ?";
            $existing = $this->db->fetchOne($sql, [$userData['username']]);
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Username already exists'
                ];
            }
            
            // Check if email already exists
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $existing = $this->db->fetchOne($sql, [$userData['email']]);
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }
            
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $userId = $this->db->insert('users', $userData);
            
            if ($userId) {
                $this->logActivity($_SESSION['user_id'], 'USER_REGISTER', 'users', $userId);
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to register user'
                ];
            }
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed'
            ];
        }
    }
    
    /**
     * Get redirect URL based on user type
     */
    private function getRedirectUrl($userType) {
        switch ($userType) {
            case 'admin':
                return 'admin/dashboard.php';
            case 'student':
                return 'student/dashboard.php';
            default:
                return 'index.php';
        }
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $this->db->insert('activity_logs', $data);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Helper functions
function auth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}

function isLoggedIn() {
    return auth()->isLoggedIn();
}

function isAdmin() {
    return auth()->isAdmin();
}

function isStudent() {
    return auth()->isStudent();
}

function requireLogin() {
    auth()->requireLogin();
}

function requireAdmin() {
    auth()->requireAdmin();
}

function requireStudent() {
    auth()->requireStudent();
}

function getCurrentUser() {
    return auth()->getCurrentUser();
}

function logout() {
    auth()->logout();
}

function csrfToken() {
    return auth()->generateCSRFToken();
}

function verifyCsrfToken($token) {
    return auth()->verifyCSRFToken($token);
}
?>
