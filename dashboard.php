<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

// Get current user data
$currentUser = $user->getCurrentUser();

includeHeader('Dashboard - AEI Lab');

// Show login success message
if (isset($_GET['login']) && $_GET['login'] === 'success') {
    showAlert('Successfully logged in! Welcome to AEI Lab Internal Tool.', 'success');
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tachometer-alt"></i> Dashboard</h5>
            </div>
            <div class="card-body">
                <h2>Welcome to AEI Lab Internal Tool</h2>
                <p class="lead">
                    This is the internal development tool for generating datasets for our 
                    Artificial Emotional Intelligence (AEI) system.
                </p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-database"></i> Dataset Generation
                                </h5>
                                <p class="card-text">Create and manage datasets for AEI training.</p>
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create Dataset
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-line"></i> Analytics
                                </h5>
                                <p class="card-text">View analytics and insights from your datasets.</p>
                                <a href="#" class="btn btn-info btn-sm">
                                    <i class="fas fa-chart-bar"></i> View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user"></i> User Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Name:</strong><br>
                    <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                </div>
                <div class="mb-3">
                    <strong>Username:</strong><br>
                    <?php echo htmlspecialchars($currentUser['username']); ?>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong><br>
                    <?php echo htmlspecialchars($currentUser['email']); ?>
                </div>
                <div class="mb-3">
                    <strong>Role:</strong><br>
                    <span class="badge bg-<?php echo $currentUser['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($currentUser['role']); ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Last Login:</strong><br>
                    <?php 
                    if ($currentUser['last_login']) {
                        echo date('d.m.Y H:i', strtotime($currentUser['last_login']));
                    } else {
                        echo 'Never';
                    }
                    ?>
                </div>
                <div class="mb-3">
                    <strong>Member Since:</strong><br>
                    <?php echo date('d.m.Y', strtotime($currentUser['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-folder"></i> My Datasets
                    </a>
                    <a href="#" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <?php if ($user->isAdmin()): ?>
                    <a href="admin.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-users-cog"></i> Admin Panel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="text-center text-muted">
                    <i class="fas fa-hourglass-half fa-2x"></i>
                    <p class="mt-2">No recent activity to display.</p>
                    <small>Start creating datasets to see your activity here.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 