<?php
/**
 * Bootstrap file - Load all necessary classes and configurations
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load classes
require_once __DIR__ . '/../classes/Setup.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Character.php';

// Initialize setup
$setup = new Setup();

// Auto-initialize database if needed
if (!$setup->isDatabaseInitialized()) {
    $setupResult = $setup->initialize();
    if (!$setupResult) {
        // If setup fails, redirect to setup page
        if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
            header('Location: setup.php');
            exit;
        }
    }
}

// Initialize database connection
$database = new Database();

// Initialize user authentication
$user = new User($database);

// Initialize character management
$character = new Character($database);

// Check if user needs to be redirected
function requireAuth() {
    global $user;
    if (!$user->isSessionValid()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Check if user is already logged in (for login page)
function redirectIfLoggedIn() {
    global $user;
    if ($user->isSessionValid()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// Function to include header
function includeHeader($title = 'AEI Lab Internal Tool') {
    global $user;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .navbar-brand {
                font-weight: bold;
                color: #007bff !important;
            }
            .login-container {
                max-width: 400px;
                margin: 100px auto;
            }
            .card {
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body>
        <?php if ($user->isLoggedIn()): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-brain"></i> AEI Lab
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="characters.php">
                        <i class="fas fa-users"></i> Characters
                    </a>
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
                    </span>
                    <?php if ($user->isAdmin()): ?>
                    <a class="nav-link" href="admin.php">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>
        <?php endif; ?>
        <div class="container mt-4">
    <?php
}

// Function to include footer
function includeFooter() {
    ?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

// Function to show alerts
function showAlert($message, $type = 'info') {
    echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}
?> 