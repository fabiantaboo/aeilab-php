<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$characterId = intval($_GET['id'] ?? 0);
if (!$characterId) {
    header('Location: characters.php');
    exit;
}

$characterData = $character->getById($characterId);
if (!$characterData) {
    header('Location: characters.php');
    exit;
}

includeHeader('Character: ' . $characterData['name'] . ' - AEI Lab');
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($characterData['name']); ?>
                    <span class="badge bg-<?php echo $characterData['type'] === 'AEI' ? 'success' : 'info'; ?> ms-2">
                        <?php echo htmlspecialchars($characterData['type']); ?>
                    </span>
                </h5>
                <div>
                    <?php if ($character->canEdit($characterId, $_SESSION['user_id'])): ?>
                        <a href="character-edit.php?id=<?php echo $characterId; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="characters.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($characterData['description']): ?>
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($characterData['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <h6>System Prompt</h6>
                    <div class="bg-light p-3 rounded">
                        <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;"><?php echo htmlspecialchars($characterData['system_prompt']); ?></pre>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Character Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Type:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $characterData['type'] === 'AEI' ? 'success' : 'info'; ?>">
                                        <?php echo htmlspecialchars($characterData['type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $characterData['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $characterData['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created by:</strong></td>
                                <td><?php echo htmlspecialchars($characterData['creator_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('F j, Y \a\t g:i A', strtotime($characterData['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Updated:</strong></td>
                                <td><?php echo date('F j, Y \a\t g:i A', strtotime($characterData['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Character Type Features</h6>
                        <?php if ($characterData['type'] === 'AEI'): ?>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-robot text-success"></i> AI-powered responses</li>
                                <li><i class="fas fa-brain text-success"></i> Intelligent conversation</li>
                                <li><i class="fas fa-cog text-success"></i> Adaptive behavior</li>
                                <li><i class="fas fa-database text-success"></i> Data-driven insights</li>
                            </ul>
                        <?php else: ?>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-user text-info"></i> Human-like personality</li>
                                <li><i class="fas fa-theater-masks text-info"></i> Role-playing scenarios</li>
                                <li><i class="fas fa-comments text-info"></i> Conversational training</li>
                                <li><i class="fas fa-heart text-info"></i> Emotional responses</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-cog"></i> Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($character->canEdit($characterId, $_SESSION['user_id'])): ?>
                        <a href="character-edit.php?id=<?php echo $characterId; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Character
                        </a>
                    <?php endif; ?>
                    
                    <a href="character-create.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create New Character
                    </a>
                    
                    <a href="characters.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> All Characters
                    </a>
                    
                    <?php if ($character->canEdit($characterId, $_SESSION['user_id'])): ?>
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="confirmDelete(<?php echo $characterId; ?>, '<?php echo addslashes($characterData['name']); ?>')">
                            <i class="fas fa-trash"></i> Delete Character
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Usage Tips</h6>
            </div>
            <div class="card-body">
                <p class="small">
                    <strong>System Prompt:</strong> This is the core instruction that defines how the character behaves and responds.
                </p>
                <p class="small">
                    <strong>AEI Characters:</strong> Use for AI-powered intelligent responses and data analysis.
                </p>
                <p class="small">
                    <strong>User Characters:</strong> Use for role-playing scenarios and human-like interactions.
                </p>
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
                <form method="POST" action="characters.php" style="display: inline;">
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