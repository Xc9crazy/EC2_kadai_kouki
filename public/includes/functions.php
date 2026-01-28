<?php
/**
 * Utility functions for validation, sanitization, and common operations
 */

/**
 * Sanitize output for HTML display
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * Minimum 8 characters, at least one letter and one number
 */
function isValidPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[a-zA-Z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Format date for display
 */
function formatDate($datetime) {
    if (empty($datetime)) {
        return '';
    }
    $dt = new DateTime($datetime);
    return $dt->format('Y年m月d日 H:i');
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove any path components
    $filename = basename($filename);
    
    // Remove any characters that aren't alphanumeric, dash, underscore, or dot
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    return $filename;
}

/**
 * Generate random filename with extension
 */
function generateRandomFilename($extension = 'png') {
    return time() . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
}

/**
 * Validate image file
 */
function isValidImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes, true);
}

/**
 * Save base64 image
 */
function saveBase64Image($base64Data, $directory = '/var/www/upload/image/') {
    // Remove data URI prefix if present
    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
    
    // Decode base64
    $imageData = base64_decode($base64Data);
    
    if ($imageData === false) {
        return false;
    }
    
    // Validate image
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedTypes, true)) {
        return false;
    }
    
    // Generate filename
    $filename = generateRandomFilename('png');
    $filepath = $directory . $filename;
    
    // Save file
    if (file_put_contents($filepath, $imageData) === false) {
        return false;
    }
    
    return $filename;
}

/**
 * Redirect with 303 See Other
 */
function redirect($url) {
    header("HTTP/1.1 303 See Other");
    header("Location: " . $url);
    exit;
}

/**
 * Get user by ID with caching
 */
function getUserById($userId) {
    static $cache = [];
    
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    
    try {
        $dbh = getDB();
        $stmt = $dbh->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $cache[$userId] = $user;
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert line breaks to <br> tags for display
 */
function nl2br_html($text) {
    return nl2br(h($text), false);
}

/**
 * Truncate text with ellipsis
 */
function truncate($text, $length = 100, $ellipsis = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $ellipsis;
}

/**
 * Check if string is empty or whitespace only
 */
function isEmptyString($str) {
    return empty(trim($str ?? ''));
}
