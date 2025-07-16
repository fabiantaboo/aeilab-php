<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$dialogId = intval($_GET['id'] ?? 0);
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
                    <h6>Dialog Messages</h6>
                    <?php if (empty($messages)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No messages yet. 
                            This dialog is ready for conversation generation.
                        </div>
                    <?php else: ?>
                        <div class="conversation-flow">
                            <?php foreach ($messages as $message): ?>
                                <div class="message mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <?php if ($message['character_type'] === 'AEI'): ?>
                                                <span class="badge bg-success">AEI</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo htmlspecialchars($message['character_name']); ?></strong>
                                                <small class="text-muted">Turn <?php echo $message['turn_number']; ?></small>
                                            </div>
                                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
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

<?php includeFooter(); ?> 