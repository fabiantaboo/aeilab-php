<?php
/**
 * User Authentication Class
 * Handles user login, logout, and session management
 */
class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Authenticate user login
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->db->query(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        session_start();
        session_regenerate_id();
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if session is valid
     * @return bool
     */
    public function isSessionValid() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current user data
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT id, username, email, first_name, last_name, role, created_at, last_login FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user is admin
     * @return bool
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Create new user (admin only)
     * @param array $userData
     * @return bool
     */
    public function createUser($userData) {
        if (!$this->isAdmin()) {
            return false;
        }
        
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        try {
            $this->db->query(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $userData['username'],
                    $userData['email'],
                    $passwordHash,
                    $userData['first_name'],
                    $userData['last_name'],
                    $userData['role'] ?? 'user'
                ]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate CSRF token
     * @return string
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * @param string $token
     * @return bool
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?> 