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
            'type' => $_POST['type'] ?? '',
            'system_prompt' => trim($_POST['system_prompt'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ];
        
        // Validate data
        $errors = $character->validate($formData);
        
        if (empty($errors)) {
            $characterId = $character->create($formData);
            
            if ($characterId) {
                $success = 'Character created successfully!';
                $formData = []; // Clear form data
            } else {
                $error = 'Failed to create character. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

$types = $character->getTypes();

includeHeader('Create Character - AEI Lab');
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Create New Character</h5>
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
                        <a href="characters.php" class="btn btn-primary">View All Characters</a>
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            Create Another
                        </button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $user->generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Character Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" 
                               maxlength="100" required>
                        <div class="form-text">Give your character a descriptive name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Character Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select character type</option>
                            <?php foreach ($types as $typeValue => $typeName): ?>
                                <option value="<?php echo htmlspecialchars($typeValue); ?>" 
                                        <?php echo ($formData['type'] ?? '') === $typeValue ? 'selected' : ''; ?>>
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
                                  rows="8" maxlength="10000" required><?php echo htmlspecialchars($formData['system_prompt'] ?? ''); ?></textarea>
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
                        <a href="characters.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Character
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Character Type Help -->
<div class="row mt-4">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-lightbulb"></i> Character Types Explained</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">AEI Characters</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-robot text-success"></i> AI-powered responses</li>
                            <li><i class="fas fa-brain text-success"></i> Intelligent conversation</li>
                            <li><i class="fas fa-cog text-success"></i> Adaptive behavior</li>
                            <li><i class="fas fa-database text-success"></i> Data-driven insights</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info">User Characters</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-user text-info"></i> Human-like personality</li>
                            <li><i class="fas fa-theater-masks text-info"></i> Role-playing scenarios</li>
                            <li><i class="fas fa-comments text-info"></i> Conversational training</li>
                            <li><i class="fas fa-heart text-info"></i> Emotional responses</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Prompt Examples -->
<div class="row mt-4">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-file-alt"></i> System Prompt Examples</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">AEI Character Example</h6>
                        <div class="bg-light p-3 rounded">
                            <small>
                                You are an AI assistant specialized in emotional intelligence. 
                                You analyze text for emotional content and provide empathetic responses. 
                                Always be understanding, supportive, and offer constructive feedback. 
                                Use emotional intelligence principles in all interactions.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info">User Character Example</h6>
                        <div class="bg-light p-3 rounded">
                            <small>
                                You are a helpful customer service representative named Sarah. 
                                You are patient, professional, and always try to solve problems. 
                                You work for a tech company and can help with product questions, 
                                technical issues, and general inquiries. Stay in character at all times.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 