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
                $characterId = intval($_POST['character_id']);
                if ($character->canEdit($characterId, $_SESSION['user_id'])) {
                    if ($character->delete($characterId)) {
                        $success = 'Character deleted successfully.';
                    } else {
                        $error = 'Failed to delete character.';
                    }
                } else {
                    $error = 'You do not have permission to delete this character.';
                }
                break;
        }
    }
}

// Get filters
$filters = [
    'type' => $_GET['type'] ?? '',
    'search' => $_GET['search'] ?? '',
    'created_by' => $_GET['my_characters'] == '1' ? $_SESSION['user_id'] : ''
];

// Get characters
$characters = $character->getAll($filters);
$stats = $character->getStats();
$types = $character->getTypes();

includeHeader('Characters - AEI Lab');
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
                <h5><i class="fas fa-users"></i> Character Management</h5>
                <a href="character-create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Character
                </a>
            </div>
            <div class="card-body">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['total']; ?></h5>
                                <p class="mb-0">Total Characters</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['aei']; ?></h5>
                                <p class="mb-0">AEI Characters</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['user']; ?></h5>
                                <p class="mb-0">User Characters</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['active']; ?></h5>
                                <p class="mb-0">Active Characters</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <?php foreach ($types as $typeValue => $typeName): ?>
                                        <option value="<?php echo htmlspecialchars($typeValue); ?>" 
                                                <?php echo $filters['type'] === $typeValue ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($typeName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search characters..." 
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="my_characters" 
                                           value="1" id="my_characters" 
                                           <?php echo !empty($filters['created_by']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="my_characters">
                                        My Characters Only
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
                
                <!-- Characters Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Creator</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($characters)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> No characters found.
                                        <a href="character-create.php">Create your first character</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($characters as $char): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($char['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $char['type'] === 'AEI' ? 'success' : 'info'; ?>">
                                                <?php echo htmlspecialchars($char['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $description = $char['description'] ?? '';
                                            echo htmlspecialchars(strlen($description) > 50 ? 
                                                substr($description, 0, 50) . '...' : $description);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($char['creator_name']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($char['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $char['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $char['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="character-view.php?id=<?php echo $char['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($character->canEdit($char['id'], $_SESSION['user_id'])): ?>
                                                    <a href="character-edit.php?id=<?php echo $char['id']; ?>" 
                                                       class="btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $char['id']; ?>, '<?php echo addslashes($char['name']); ?>')">
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
                <h5 class="modal-title">Delete Character</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the character "<span id="characterName"></span>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="character_id" id="deleteCharacterId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('characterName').textContent = name;
    document.getElementById('deleteCharacterId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php includeFooter(); ?> 