<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($action) {
            case 'delete':
                $dialogId = intval($_POST['dialog_id']);
                if ($dialog->canEdit($dialogId, $_SESSION['user_id'])) {
                    if ($dialog->delete($dialogId)) {
                        $success = 'Dialog deleted successfully.';
                    } else {
                        $error = 'Failed to delete dialog.';
                    }
                } else {
                    $error = 'You do not have permission to delete this dialog.';
                }
                break;
        }
    }
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'created_by' => $_GET['my_dialogs'] == '1' ? $_SESSION['user_id'] : ''
];

// Get dialogs
$dialogs = $dialog->getAll($filters);
$stats = $dialog->getStats();
$statuses = $dialog->getStatuses();

includeHeader('Dialogs - AEI Lab');
?>

<div class="row">
    <div class="col-md-12">
        <?php if ($error): ?>
            <?php showAlert($error, 'danger'); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php showAlert($success, 'success'); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-comments"></i> Dialog Management</h5>
                <a href="dialog-create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Dialog
                </a>
            </div>
            <div class="card-body">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['total']; ?></h5>
                                <p class="mb-0">Total Dialogs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['draft']; ?></h5>
                                <p class="mb-0">Draft</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['in_progress']; ?></h5>
                                <p class="mb-0">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['completed']; ?></h5>
                                <p class="mb-0">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $statusValue => $statusName): ?>
                                        <option value="<?php echo htmlspecialchars($statusValue); ?>" 
                                                <?php echo $filters['status'] === $statusValue ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($statusName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search dialogs..." 
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="my_dialogs" 
                                           value="1" id="my_dialogs" 
                                           <?php echo !empty($filters['created_by']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="my_dialogs">
                                        My Dialogs Only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Dialogs Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Dialog Name</th>
                                <th>Topic</th>
                                <th>Characters</th>
                                <th>Turns</th>
                                <th>Status</th>
                                <th>Creator</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dialogs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> No dialogs found.
                                        <a href="dialog-create.php">Create your first dialog</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dialogs as $dialogItem): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($dialogItem['name']); ?></strong>
                                            <?php if ($dialogItem['description']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($dialogItem['description'], 0, 50)); ?>
                                                    <?php echo strlen($dialogItem['description']) > 50 ? '...' : ''; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($dialogItem['topic']); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="badge bg-success mb-1">
                                                    AEI: <?php echo htmlspecialchars($dialogItem['aei_character_name']); ?>
                                                </span>
                                                <span class="badge bg-info">
                                                    User: <?php echo htmlspecialchars($dialogItem['user_character_name']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $dialogItem['turns_per_topic']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'in_progress' => 'warning',
                                                'completed' => 'success'
                                            ];
                                            $statusColor = $statusColors[$dialogItem['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($statuses[$dialogItem['status']] ?? $dialogItem['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($dialogItem['creator_name']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($dialogItem['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="dialog-view.php?id=<?php echo $dialogItem['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($dialog->canEdit($dialogItem['id'], $_SESSION['user_id'])): ?>
                                                    <a href="dialog-edit.php?id=<?php echo $dialogItem['id']; ?>" 
                                                       class="btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $dialogItem['id']; ?>, '<?php echo addslashes($dialogItem['name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Dialog</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the dialog "<span id="dialogName"></span>"?</p>
                <p class="text-muted">This action cannot be undone and will also delete all associated messages.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="dialog_id" id="deleteDialogId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('dialogName').textContent = name;
    document.getElementById('deleteDialogId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php includeFooter(); ?> 