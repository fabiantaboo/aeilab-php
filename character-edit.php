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

// Check permissions
if (!$character->canEdit($characterId, $_SESSION['user_id'])) {
    header('Location: characters.php');
    exit;
}

$error = '';
$success = '';
$formData = $characterData;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'system_prompt' => trim($_POST['system_prompt'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];
        
        // Validate data
        $errors = $character->validate($formData);
        
        if (empty($errors)) {
            if ($character->update($characterId, $formData)) {
                $success = 'Character updated successfully!';
                $characterData = $character->getById($characterId); // Refresh data
                $formData = $characterData;
            } else {
                $error = 'Failed to update character. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

$types = $character->getTypes();

includeHeader('Edit Character: ' . $characterData['name'] . ' - AEI Lab');
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-edit"></i> Edit Character</h5>
                <div>
                    <a href="character-view.php?id=<?php echo $characterId; ?>" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="characters.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
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
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Character Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($formData['name']); ?>" 
                               maxlength="100" required>
                        <div class="form-text">Give your character a descriptive name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Character Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select character type</option>
                            <?php foreach ($types as $typeValue => $typeName): ?>
                                <option value="<?php echo htmlspecialchars($typeValue); ?>" 
                                        <?php echo $formData['type'] === $typeValue ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($typeName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            <strong>AEI:</strong> AI-powered character for intelligent responses<br>
                            <strong>User:</strong> Human-like character for role-playing scenarios
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt *</label>
                        <textarea class="form-control" id="system_prompt" name="system_prompt" 
                                  rows="8" maxlength="10000" required><?php echo htmlspecialchars($formData['system_prompt']); ?></textarea>
                        <div class="form-text">
                            Define the character's personality, behavior, and response style. 
                            This is the core instruction that guides how the character will interact.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" maxlength="1000"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        <div class="form-text">Optional: Brief description of the character for reference.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="character-view.php?id=<?php echo $characterId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Character
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Character Information -->
<div class="row mt-4">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Character Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Created by:</strong> <?php echo htmlspecialchars($characterData['creator_name']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('F j, Y', strtotime($characterData['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $characterData['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $characterData['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($characterData['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 