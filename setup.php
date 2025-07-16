<?php
// Manual setup page for AEI Lab Internal Tool
require_once 'config/config.php';
require_once 'classes/Setup.php';

$setup = new Setup();
$message = '';
$messageType = '';

// Handle manual setup trigger
if (isset($_POST['run_setup'])) {
    if ($setup->initialize()) {
        $message = 'Setup completed successfully! Database and tables created.';
        $messageType = 'success';
    } else {
        $message = 'Setup failed! Please check your database configuration and try again.';
        $messageType = 'danger';
    }
}

$status = $setup->getSetupStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - AEI Lab Internal Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .status-icon {
            font-size: 1.2em;
            margin-right: 10px;
        }
        .status-success {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-brain"></i> AEI Lab Internal Tool - Setup</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <h5>Database Setup Status</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-database status-icon <?php echo $status['database_exists'] ? 'status-success' : 'status-error'; ?>"></i>
                                        Database Created
                                    </span>
                                    <span class="badge bg-<?php echo $status['database_exists'] ? 'success' : 'danger'; ?>">
                                        <?php echo $status['database_exists'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-table status-icon <?php echo $status['tables_exist'] ? 'status-success' : 'status-error'; ?>"></i>
                                        Tables Created
                                    </span>
                                    <span class="badge bg-<?php echo $status['tables_exist'] ? 'success' : 'danger'; ?>">
                                        <?php echo $status['tables_exist'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-user-shield status-icon <?php echo $status['admin_exists'] ? 'status-success' : 'status-error'; ?>"></i>
                                        Admin User Created
                                    </span>
                                    <span class="badge bg-<?php echo $status['admin_exists'] ? 'success' : 'danger'; ?>">
                                        <?php echo $status['admin_exists'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Configuration</h6>
                                    <p><strong>Database Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?></p>
                                    <p><strong>Database Name:</strong> <?php echo htmlspecialchars(DB_NAME); ?></p>
                                    <p><strong>Database User:</strong> <?php echo htmlspecialchars(DB_USER); ?></p>
                                    <p><strong>Character Set:</strong> <?php echo htmlspecialchars(DB_CHARSET); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Overall Status</h6>
                            <?php if ($status['ready']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> 
                                    <strong>Setup Complete!</strong><br>
                                    The application is ready to use.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Setup Incomplete!</strong><br>
                                    Please run the setup process.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Actions</h6>
                            <?php if (!$status['ready']): ?>
                                <form method="POST" action="">
                                    <button type="submit" name="run_setup" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Run Setup
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-success">
                                    <i class="fas fa-arrow-right"></i> Go to Login
                                </a>
                            <?php endif; ?>
                            
                            <a href="index.php" class="btn btn-outline-secondary mt-2">
                                <i class="fas fa-home"></i> Back to Application
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($status['ready']): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle"></i> Demo Login Credentials</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Username:</strong> <code>admin</code></p>
                            <p><strong>Password:</strong> <code>admin123</code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Role:</strong> Administrator</p>
                            <p><strong>Email:</strong> admin@aeilab.com</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-list"></i> Tables Created</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-users"></i> <strong>users</strong> - User accounts and authentication</li>
                        <li><i class="fas fa-database"></i> <strong>datasets</strong> - AEI dataset management</li>
                        <li><i class="fas fa-history"></i> <strong>activity_log</strong> - User activity tracking</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 