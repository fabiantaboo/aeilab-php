<?php
require_once 'includes/bootstrap.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';
$success = '';

// Show logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if ($user->login($username, $password)) {
            header('Location: dashboard.php?login=success');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

includeHeader('Login - AEI Lab');
?>

<div class="login-container">
    <div class="card">
        <div class="card-header bg-primary text-white text-center">
            <h4><i class="fas fa-brain"></i> AEI Lab Internal Tool</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <?php showAlert($error, 'danger'); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php showAlert($success, 'success'); ?>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center text-muted">
            <small>
                <i class="fas fa-info-circle"></i> Internal Tool for AEI Lab Team
            </small>
        </div>
    </div>
</div>

<div class="mt-4 text-center">
    <div class="alert alert-info">
        <strong>Demo Credentials:</strong><br>
        Username: <code>admin</code><br>
        Password: <code>admin123</code>
    </div>
</div>

<?php includeFooter(); ?> 