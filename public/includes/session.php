<?php
/**
 * Session management with security enhancements
 * Includes CSRF token generation and validation
 */

/**
 * Initialize secure session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (getenv('SESSION_SECURE') === 'true') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } else if (time() - $_SESSION['created_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
        
        // 【修正】セッション開始時にCSRFトークンを生成
        generateCsrfToken();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return !empty($_SESSION['login_user_id']);
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("HTTP/1.1 302 Found");
        header("Location: /login.php");
        exit;
    }
}

/**
 * Get current logged-in user ID
 */
function getCurrentUserId() {
    return $_SESSION['login_user_id'] ?? null;
}

/**
 * Set logged-in user
 */
function setLoggedInUser($userId) {
    $_SESSION['login_user_id'] = $userId;
    session_regenerate_id(true); // Regenerate session ID on login
    // 【修正】ログイン時に新しいCSRFトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token for POST requests
 */
function requireCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            // 【修正】より詳細なエラーログを出力
            error_log("CSRF token validation failed. Session token: " . 
                     (isset($_SESSION['csrf_token']) ? 'exists' : 'missing') . 
                     ", POST token: " . ($token ? 'provided' : 'missing'));
            http_response_code(403);
            die('不正なリクエストです');
        }
    }
}

/**
 * Get CSRF token input field HTML
 */
function csrfTokenField() {
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}