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

// Handle pairing actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_pairing') {
            $partnerId = intval($_POST['partner_id'] ?? 0);
            
            if ($characterData['type'] === 'AEI') {
                $success = $character->createPairing($characterId, $partnerId, $_SESSION['user_id']);
            } else {
                $success = $character->createPairing($partnerId, $characterId, $_SESSION['user_id']);
            }
            
            if ($success) {
                $message = 'Character pairing created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to create pairing. Check that both characters exist and are different types.';
                $messageType = 'danger';
            }
        } elseif ($action === 'remove_pairing') {
            $partnerId = intval($_POST['partner_id'] ?? 0);
            
            if ($characterData['type'] === 'AEI') {
                $success = $character->removePairing($characterId, $partnerId);
            } else {
                $success = $character->removePairing($partnerId, $characterId);
            }
            
            if ($success) {
                $message = 'Character pairing removed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to remove pairing.';
                $messageType = 'danger';
            }
        }
    }
}

// Get pairings for this character
$pairings = $character->getPairingsForCharacter($characterId);

// Get available characters for pairing
if ($characterData['type'] === 'AEI') {
    $availableForPairing = $character->getAll(['type' => 'User', 'is_active' => 1]);
} else {
    $availableForPairing = $character->getAll(['type' => 'AEI', 'is_active' => 1]);
}

// Filter out already paired characters
$pairedIds = array_column($pairings, $characterData['type'] === 'AEI' ? 'user_character_id' : 'aei_character_id');
$availableForPairing = array_filter($availableForPairing, function($char) use ($pairedIds) {
    return !in_array($char['id'], $pairedIds);
});

includeHeader('Character: ' . $characterData['name'] . ' - AEI Lab');
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

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
        
        <!-- Character Pairings Section -->
        <div class="card mt-4" id="pairings">
            <div class="card-header">
                <h5><i class="fas fa-link"></i> Character Pairings</h5>
                <small class="text-muted">
                    Paired characters work best together and will be suggested when creating dialogs.
                </small>
            </div>
            <div class="card-body">
                <?php if (!empty($pairings)): ?>
                    <h6>Current Pairings</h6>
                    <div class="row mb-4">
                        <?php foreach ($pairings as $pairing): ?>
                            <div class="col-md-6 mb-2">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">
                                                    <i class="fas fa-<?php echo $pairing['partner_type'] === 'AEI' ? 'robot text-success' : 'user text-info'; ?>"></i>
                                                    <?php echo htmlspecialchars($pairing['partner_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo $pairing['partner_type']; ?> Character
                                                </small><br>
                                                <small class="text-muted">
                                                    Paired by <?php echo htmlspecialchars($pairing['creator_name']); ?>
                                                    on <?php echo date('M j, Y', strtotime($pairing['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if ($character->canEdit($characterId, $_SESSION['user_id'])): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="remove_pairing">
                                                    <input type="hidden" name="partner_id" value="<?php echo $characterData['type'] === 'AEI' ? $pairing['user_character_id'] : $pairing['aei_character_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Remove this pairing?')">
                                                        <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($character->canEdit($characterId, $_SESSION['user_id']) && !empty($availableForPairing)): ?>
                    <h6>Add New Pairing</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add_pairing">
                        <div class="col-md-8">
                            <select class="form-select" name="partner_id" required>
                                <option value="">Select <?php echo $characterData['type'] === 'AEI' ? 'User' : 'AEI'; ?> Character to Pair</option>
                                <?php foreach ($availableForPairing as $char): ?>
                                    <option value="<?php echo $char['id']; ?>">
                                        <?php echo htmlspecialchars($char['name']); ?>
                                        <?php if ($char['description']): ?>
                                            - <?php echo htmlspecialchars(substr($char['description'], 0, 50)); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-link"></i> Create Pairing
                            </button>
                        </div>
                    </form>
                <?php elseif (empty($availableForPairing)): ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle"></i>
                        <p class="mt-2">
                            No <?php echo $characterData['type'] === 'AEI' ? 'User' : 'AEI'; ?> characters available for pairing.
                            <?php if (empty($pairings)): ?>
                                <br>Create some <?php echo $characterData['type'] === 'AEI' ? 'User' : 'AEI'; ?> characters to set up pairings.
                            <?php endif; ?>
                        </p>
                        <a href="character-create.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> Create <?php echo $characterData['type'] === 'AEI' ? 'User' : 'AEI'; ?> Character
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <small class="text-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>How pairings work:</strong> When you select a character in the dialog creation form, 
                        paired characters will be highlighted with a ★ symbol and suggested automatically.
                    </small>
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
                    <?php if ($characterData['type'] === 'AEI' && $characterData['is_active']): ?>
                        <a href="character-chat.php?character_id=<?php echo $characterId; ?>" class="btn btn-primary">
                            <i class="fas fa-comments"></i> Chat with Character
                        </a>
                        <hr class="my-2">
                    <?php endif; ?>
                    
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