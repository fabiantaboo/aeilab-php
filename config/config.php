<?php
// AEI Lab Internal Tool Configuration

// Database Configuration
define('DB_HOST', '5.161.216.58');
define('DB_NAME', 'aeilab-php_');
define('DB_USER', 'aeilab-php');
define('DB_PASS', 'A~UvMzb*2b0e4wbs');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'AEI Lab Internal Tool');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://aeilab.tabootwin.com');

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);

// Error Reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
session_start();
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Timezone
date_default_timezone_set('Europe/Berlin');
?> 