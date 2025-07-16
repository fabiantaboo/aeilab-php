<?php
require_once 'includes/bootstrap.php';

// Require authentication and admin role
requireAuth();
if (!$user->isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token.';
    } else {
        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'role' => $_POST['role'] ?? 'user'
        ];
        
        // Basic validation
        if (empty($userData['username']) || empty($userData['email']) || 
            empty($userData['password']) || empty($userData['first_name']) || 
            empty($userData['last_name'])) {
            $error = 'All fields are required.';
        } elseif (strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            if ($user->createUser($userData)) {
                $success = 'User created successfully!';
            } else {
                $error = 'Failed to create user. Username or email might already exist.';
            }
        }
    }
}

// Get all users
$users = $database->fetchAll("SELECT id, username, email, first_name, last_name, role, created_at, last_login, is_active FROM users ORDER BY created_at DESC");

includeHeader('Admin Panel - AEI Lab');
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> User Management</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $userItem): ?>
                            <tr>
                                <td><?php echo $userItem['id']; ?></td>
                                <td><?php echo htmlspecialchars($userItem['username']); ?></td>
                                <td><?php echo htmlspecialchars($userItem['first_name'] . ' ' . $userItem['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $userItem['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($userItem['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $userItem['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $userItem['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($userItem['last_login']) {
                                        echo date('d.m.Y H:i', strtotime($userItem['last_login']));
                                    } else {
                                        echo '<span class="text-muted">Never</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($userItem['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-plus"></i> Create New User</h5>
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
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="create_user" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Admin Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button class="btn btn-outline-secondary btn-sm" disabled>
                        <i class="fas fa-database"></i> Database Backup
                    </button>
                    <button class="btn btn-outline-info btn-sm" disabled>
                        <i class="fas fa-chart-line"></i> System Stats
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 