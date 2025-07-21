<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Ungültiges Sicherheitstoken. Bitte versuchen Sie es erneut.';
    } else {
        switch ($action) {
            case 'restart_job':
                $jobId = intval($_POST['job_id'] ?? 0);
                if ($jobId > 0) {
                    $jobData = $dialogJob->getById($jobId);
                    if ($jobData && $jobData['dialog_id'] == $dialogId) {
                        // Check if user can restart (admins or dialog creators)
                        if ($user->isAdmin() || ($dialogData && $dialogData['created_by'] == $_SESSION['user_id'])) {
                            if ($dialogJob->restart($jobId)) {
                                $success = 'Dialog-Job wurde erfolgreich neu gestartet und wird in Kürze verarbeitet.';
                            } else {
                                $error = 'Fehler beim Neustart des Dialog-Jobs. Bitte versuchen Sie es später erneut.';
                            }
                        } else {
                            $error = 'Sie haben keine Berechtigung, diesen Dialog-Job neu zu starten.';
                        }
                    } else {
                        $error = 'Job nicht gefunden oder gehört nicht zu diesem Dialog.';
                    }
                } else {
                    $error = 'Ungültige Job-ID.';
                }
                break;
        }
    }
    
    // Redirect to avoid resubmission
    if ($success || $error) {
        $params = [];
        if ($success) $params[] = 'success=' . urlencode($success);
        if ($error) $params[] = 'error=' . urlencode($error);
        $queryString = !empty($params) ? '?' . implode('&', $params) : '';
        header('Location: dialog-view.php?id=' . $dialogId . $queryString);
        exit;
    }
}

$dialogId = intval($_GET['id'] ?? 0);

// Get success/error messages from query parameters
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
if (!$dialogId) {
    header('Location: dialogs.php');
    exit;
}

$dialogData = $dialog->getById($dialogId);
if (!$dialogData) {
    header('Location: dialogs.php');
    exit;
}

// Get dialog messages
$messages = $dialog->getMessages($dialogId);
$statuses = $dialog->getStatuses();

// Get job status
$jobStatus = $dialogJob->getByDialogId($dialogId);

includeHeader('Dialog: ' . $dialogData['name'] . ' - AEI Lab');
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="fas fa-comments"></i> 
                    <?php echo htmlspecialchars($dialogData['name']); ?>
                    <?php
                    $statusColors = [
                        'draft' => 'secondary',
                        'in_progress' => 'warning',
                        'completed' => 'success'
                    ];
                    $statusColor = $statusColors[$dialogData['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $statusColor; ?> ms-2">
                        <?php echo htmlspecialchars($statuses[$dialogData['status']] ?? $dialogData['status']); ?>
                    </span>
                </h5>
                <div>
                    <?php if ($dialog->canEdit($dialogId, $_SESSION['user_id'])): ?>
                        <a href="dialog-edit.php?id=<?php echo $dialogId; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="dialogs.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($dialogData['description']): ?>
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($dialogData['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Job Status Display -->
                <?php if ($jobStatus): ?>
                    <div class="mb-4">
                        <h6>Background Job Status</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted">Status:</small><br>
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger'
                                ];
                                $statusIcon = [
                                    'pending' => 'clock',
                                    'in_progress' => 'spinner fa-spin',
                                    'completed' => 'check-circle',
                                    'failed' => 'exclamation-triangle'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$jobStatus['status']]; ?>">
                                    <i class="fas fa-<?php echo $statusIcon[$jobStatus['status']]; ?>"></i>
                                    <?php echo ucfirst($jobStatus['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Progress:</small><br>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo round(($jobStatus['current_turn'] / $jobStatus['max_turns']) * 100, 1); ?>%">
                                        <?php echo $jobStatus['current_turn']; ?>/<?php echo $jobStatus['max_turns']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Next Turn:</small><br>
                                <span class="badge bg-<?php echo $jobStatus['next_character_type'] === 'AEI' ? 'primary' : 'secondary'; ?>">
                                    <?php echo $jobStatus['next_character_type']; ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Last Processed:</small><br>
                                <small class="text-muted">
                                    <?php echo $jobStatus['last_processed_at'] ? date('H:i:s', strtotime($jobStatus['last_processed_at'])) : 'Never'; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($jobStatus['status'] === 'failed' && $jobStatus['error_message']): ?>
                            <div class="mt-2">
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> Error: <?php echo htmlspecialchars($jobStatus['error_message']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                        <?php if ($jobStatus['status'] === 'failed'): ?>
                            <div class="mt-3">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="restart_job">
                                    <input type="hidden" name="job_id" value="<?php echo $jobStatus['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $user->getCSRFToken(); ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm" 
                                            onclick="return confirm('Sind Sie sicher, dass Sie den fehlgeschlagenen Dialog-Job neu starten möchten?')">
                                        <i class="fas fa-redo"></i> Dialog neu starten
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                            <div class="mt-2 small text-muted">
                                Debug: Status = "<?php echo $jobStatus['status'] ?? 'null'; ?>", 
                                Job ID = <?php echo $jobStatus['id'] ?? 'null'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Topic</h6>
                        <p class="lead"><?php echo htmlspecialchars($dialogData['topic']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Turns per Topic</h6>
                        <p class="lead">
                            <span class="badge bg-info"><?php echo $dialogData['turns_per_topic']; ?></span>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>AEI Character</h6>
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($dialogData['aei_character_name']); ?></h6>
                                <p class="card-text">AI-powered intelligent responses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>User Character</h6>
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($dialogData['user_character_name']); ?></h6>
                                <p class="card-text">Human-like role-playing character</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dialog Messages -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Dialog Messages</h6>
                        <?php if (!empty($messages)): ?>
                            <div class="btn-group btn-group-sm">
                                <a href="download_anthropic_request.php?dialog_id=<?php echo $dialogId; ?>&bulk=1" 
                                   class="btn btn-outline-primary btn-sm" 
                                   title="Download all Anthropic requests as ZIP">
                                    <i class="fas fa-download"></i> Download All JSON
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($messages)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No messages yet. 
                            This dialog is ready for conversation generation.
                        </div>
                    <?php else: ?>
                        <div class="conversation-flow">
                            <?php foreach ($messages as $message): ?>
                                <div class="message mb-3 p-3 rounded <?php echo $message['character_type'] === 'AEI' ? 'bg-light border-start border-success border-4' : 'bg-white border-start border-info border-4'; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <?php if ($message['character_type'] === 'AEI'): ?>
                                                <span class="badge bg-success">AEI</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($message['character_name']); ?></strong>
                                                    <small class="text-muted ms-2">Turn <?php echo $message['turn_number']; ?></small>
                                                    <small class="text-muted ms-2"><?php echo date('H:i:s', strtotime($message['created_at'])); ?></small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($message['anthropic_request_json']): ?>
                                                        <a href="download_anthropic_request.php?message_id=<?php echo $message['id']; ?>" 
                                                           class="btn btn-outline-primary btn-sm" 
                                                           title="Download Anthropic Request JSON">
                                                            <i class="fas fa-download"></i> JSON
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="btn btn-outline-secondary btn-sm disabled" 
                                                              title="No Anthropic request data available">
                                                            <i class="fas fa-download"></i> JSON
                                                        </span>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="showMessageDetails(<?php echo $message['id']; ?>)" 
                                                            title="Show message details">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            
                                            <!-- Message Details (hidden by default) -->
                                            <div id="message-details-<?php echo $message['id']; ?>" class="collapse mt-2">
                                                <div class="card bg-light">
                                                    <div class="card-body small">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <strong>Character:</strong> <?php echo htmlspecialchars($message['character_name']); ?><br>
                                                                <strong>Type:</strong> <?php echo htmlspecialchars($message['character_type']); ?><br>
                                                                <strong>Turn:</strong> <?php echo $message['turn_number']; ?><br>
                                                                <strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($message['created_at'])); ?><br>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong>Message Length:</strong> <?php echo strlen($message['message']); ?> chars<br>
                                                                <strong>Word Count:</strong> <?php echo str_word_count($message['message']); ?> words<br>
                                                                <strong>Has API Data:</strong> <?php echo $message['anthropic_request_json'] ? 'Yes' : 'No'; ?><br>
                                                                <?php if ($message['anthropic_request_json']): ?>
                                                                    <strong>API Data Size:</strong> <?php echo round(strlen($message['anthropic_request_json']) / 1024, 2); ?> KB<br>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Dialog Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($statuses[$dialogData['status']] ?? $dialogData['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Creator:</strong></td>
                        <td><?php echo htmlspecialchars($dialogData['creator_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($dialogData['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Updated:</strong></td>
                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($dialogData['updated_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Messages:</strong></td>
                        <td><?php echo count($messages); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-cog"></i> Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($dialog->canEdit($dialogId, $_SESSION['user_id'])): ?>
                        <a href="dialog-edit.php?id=<?php echo $dialogId; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Dialog
                        </a>
                    <?php endif; ?>
                    
                    <a href="character-view.php?id=<?php echo $dialogData['aei_character_id']; ?>" class="btn btn-outline-success">
                        <i class="fas fa-robot"></i> View AEI Character
                    </a>
                    
                    <a href="character-view.php?id=<?php echo $dialogData['user_character_id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-user"></i> View User Character
                    </a>
                    
                    <a href="dialog-create.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus"></i> Create New Dialog
                    </a>
                    
                    <a href="dialogs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> All Dialogs
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-lightbulb"></i> Dialog Purpose</h6>
            </div>
            <div class="card-body">
                <p class="small">
                    This dialog setup defines how an AEI character and a User character 
                    will interact around the topic "<strong><?php echo htmlspecialchars($dialogData['topic']); ?></strong>".
                </p>
                <p class="small">
                    The conversation will run for <strong><?php echo $dialogData['turns_per_topic']; ?> turns</strong> 
                    to generate training data for the AEI system.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function showMessageDetails(messageId) {
    const detailsDiv = document.getElementById('message-details-' + messageId);
    const isVisible = detailsDiv.classList.contains('show');
    
    if (isVisible) {
        detailsDiv.classList.remove('show');
    } else {
        detailsDiv.classList.add('show');
    }
}

// Auto-refresh dialog status every 30 seconds
setInterval(function() {
    // Check if there's an active job
    const jobStatusElement = document.querySelector('.badge.bg-warning, .badge.bg-info');
    if (jobStatusElement) {
        location.reload();
    }
}, 30000);
</script>

<?php includeFooter(); ?> 