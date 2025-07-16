<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$error = '';
$success = '';
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'aei_character_id' => intval($_POST['aei_character_id'] ?? 0),
            'user_character_id' => intval($_POST['user_character_id'] ?? 0),
            'topic' => trim($_POST['topic'] ?? ''),
            'turns_per_topic' => intval($_POST['turns_per_topic'] ?? 5),
            'created_by' => $_SESSION['user_id']
        ];
        
        // Validate data
        $errors = $dialog->validate($formData);
        
        if (empty($errors)) {
            $dialogId = $dialog->create($formData);
            
            if ($dialogId) {
                $success = 'Dialog created successfully!';
                $formData = []; // Clear form data
            } else {
                $error = 'Failed to create dialog. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Get characters for selection
$aeiCharacters = $dialog->getAEICharacters();
$userCharacters = $dialog->getUserCharacters();

includeHeader('Create Dialog - AEI Lab');
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Create New Dialog</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <hr>
                        <a href="dialogs.php" class="btn btn-primary">View All Dialogs</a>
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            Create Another
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($aeiCharacters) || empty($userCharacters)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Missing Characters!</strong><br>
                        You need at least one AEI character and one User character to create a dialog.
                        <hr>
                        <a href="character-create.php" class="btn btn-warning">Create Characters</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Dialog Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" 
                                       maxlength="100" required>
                                <div class="form-text">Give your dialog a descriptive name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="turns_per_topic" class="form-label">Turns per Topic *</label>
                                <input type="number" class="form-control" id="turns_per_topic" name="turns_per_topic" 
                                       value="<?php echo htmlspecialchars($formData['turns_per_topic'] ?? '5'); ?>" 
                                       min="1" max="50" required>
                                <div class="form-text">How many conversation turns for this topic (1-50).</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="topic" class="form-label">Dialog Topic *</label>
                        <input type="text" class="form-control" id="topic" name="topic" 
                               value="<?php echo htmlspecialchars($formData['topic'] ?? ''); ?>" 
                               maxlength="200" required>
                        <div class="form-text">What topic should the characters discuss?</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="aei_character_id" class="form-label">AEI Character *</label>
                                <select class="form-select" id="aei_character_id" name="aei_character_id" required>
                                    <option value="">Select AEI Character</option>
                                    <?php foreach ($aeiCharacters as $char): ?>
                                        <option value="<?php echo $char['id']; ?>" 
                                                <?php echo ($formData['aei_character_id'] ?? 0) == $char['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($char['name']); ?>
                                            <?php if ($char['description']): ?>
                                                - <?php echo htmlspecialchars(substr($char['description'], 0, 50)); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the AI character for intelligent responses.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="user_character_id" class="form-label">User Character *</label>
                                <select class="form-select" id="user_character_id" name="user_character_id" required>
                                    <option value="">Select User Character</option>
                                    <?php foreach ($userCharacters as $char): ?>
                                        <option value="<?php echo $char['id']; ?>" 
                                                <?php echo ($formData['user_character_id'] ?? 0) == $char['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($char['name']); ?>
                                            <?php if ($char['description']): ?>
                                                - <?php echo htmlspecialchars(substr($char['description'], 0, 50)); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the human-like character for role-playing.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" maxlength="1000"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        <div class="form-text">Optional: Brief description of the dialog scenario.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dialogs.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" 
                                <?php echo (empty($aeiCharacters) || empty($userCharacters)) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save"></i> Create Dialog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Character Preview Cards -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="text-success"><i class="fas fa-robot"></i> Available AEI Characters</h6>
            </div>
            <div class="card-body">
                <?php if (empty($aeiCharacters)): ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No AEI characters available.<br>
                        <a href="character-create.php" class="btn btn-success btn-sm mt-2">
                            <i class="fas fa-plus"></i> Create AEI Character
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach (array_slice($aeiCharacters, 0, 3) as $char): ?>
                            <div class="col-md-6 mb-2">
                                <div class="card card-body bg-light">
                                    <h6 class="card-title"><?php echo htmlspecialchars($char['name']); ?></h6>
                                    <p class="card-text small">
                                        <?php echo htmlspecialchars(substr($char['description'] ?? 'No description', 0, 60)); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($aeiCharacters) > 3): ?>
                        <div class="text-center">
                            <small class="text-muted">+<?php echo count($aeiCharacters) - 3; ?> more available</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="text-info"><i class="fas fa-user"></i> Available User Characters</h6>
            </div>
            <div class="card-body">
                <?php if (empty($userCharacters)): ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No User characters available.<br>
                        <a href="character-create.php" class="btn btn-info btn-sm mt-2">
                            <i class="fas fa-plus"></i> Create User Character
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach (array_slice($userCharacters, 0, 3) as $char): ?>
                            <div class="col-md-6 mb-2">
                                <div class="card card-body bg-light">
                                    <h6 class="card-title"><?php echo htmlspecialchars($char['name']); ?></h6>
                                    <p class="card-text small">
                                        <?php echo htmlspecialchars(substr($char['description'] ?? 'No description', 0, 60)); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($userCharacters) > 3): ?>
                        <div class="text-center">
                            <small class="text-muted">+<?php echo count($userCharacters) - 3; ?> more available</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Topic Examples -->
<div class="row mt-4">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-lightbulb"></i> Topic Examples</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>Customer Service</h6>
                        <ul class="list-unstyled small">
                            <li>• Product return request</li>
                            <li>• Technical support issue</li>
                            <li>• Billing inquiry</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6>Educational</h6>
                        <ul class="list-unstyled small">
                            <li>• Learning about climate change</li>
                            <li>• Explaining quantum physics</li>
                            <li>• Language learning practice</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6>Personal</h6>
                        <ul class="list-unstyled small">
                            <li>• Career advice discussion</li>
                            <li>• Health and wellness tips</li>
                            <li>• Hobby recommendations</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 