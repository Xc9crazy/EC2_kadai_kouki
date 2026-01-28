<?php
/**
 * Common initialization file
 * Include this at the top of every PHP file
 */

// Error reporting settings
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Load required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

// Initialize session
initSecureSession();
