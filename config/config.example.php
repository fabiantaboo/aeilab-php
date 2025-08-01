<?php
// AEI Lab Internal Tool Configuration - EXAMPLE
// Copy this file to config.php and adjust the values

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'aeilab_internal');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'AEI Lab Internal Tool');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/aeilab-php/'); // Adjust to your domain/path

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);

// Anthropic API Configuration
define('ANTHROPIC_API_KEY', 'your_anthropic_api_key_here');
define('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022');
define('ANTHROPIC_MAX_TOKENS', 1000);

// Error Reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration - MUST be before session_start()
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Timezone
date_default_timezone_set('Europe/Berlin');
?> 