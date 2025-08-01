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

// Ensure rating columns exist and add them if they don't
$dialog->ensureRatingColumns();
$hasRatingColumns = $dialog->hasRatingColumns();

// Get rating statistics
$ratingStats = $dialog->getRatingStats($dialogId);

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
                                                    <?php if ($hasRatingColumns): ?>
                                                        <!-- Rating buttons -->
                                                        <button class="btn btn-outline-success btn-sm rating-btn" 
                                                                data-message-id="<?php echo $message['id']; ?>" 
                                                                data-rating-type="up"
                                                                title="Thumbs up"
                                                                <?php if (intval($message['rating_thumbs_up'] ?? 0) > 0): ?>style="background-color: #198754; color: white;"<?php endif; ?>>
                                                            <i class="fas fa-thumbs-up"></i>
                                                            <span class="rating-count-up"><?php echo intval($message['rating_thumbs_up'] ?? 0) ?: ''; ?></span>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm rating-btn" 
                                                                data-message-id="<?php echo $message['id']; ?>" 
                                                                data-rating-type="down"
                                                                title="Thumbs down"
                                                                <?php if (intval($message['rating_thumbs_down'] ?? 0) > 0): ?>style="background-color: #dc3545; color: white;"<?php endif; ?>>
                                                            <i class="fas fa-thumbs-down"></i>
                                                            <span class="rating-count-down"><?php echo intval($message['rating_thumbs_down'] ?? 0) ?: ''; ?></span>
                                                        </button>
                                                    <?php endif; ?>
                                                    
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
                                            
                                            <!-- Emotional State Display for AEI messages -->
                                            <?php if ($message['character_type'] === 'AEI'): ?>
                                                <?php
                                                // Check if any emotion data exists for this message
                                                $hasEmotions = false;
                                                $emotions = [
                                                    'joy', 'sadness', 'fear', 'anger', 'surprise', 'disgust',
                                                    'trust', 'anticipation', 'shame', 'love', 'contempt', 
                                                    'loneliness', 'pride', 'envy', 'nostalgia', 'gratitude',
                                                    'frustration', 'boredom'
                                                ];
                                                foreach ($emotions as $emotion) {
                                                    if (isset($message["aei_$emotion"]) && $message["aei_$emotion"] !== null) {
                                                        $hasEmotions = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if ($hasEmotions): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-brain"></i> Emotional State:
                                                            <button class="btn btn-sm btn-outline-secondary ms-1" 
                                                                    onclick="toggleEmotions(<?php echo $message['id']; ?>)"
                                                                    id="emotion-toggle-<?php echo $message['id']; ?>">
                                                                Show Emotions
                                                            </button>
                                                        </small>
                                                        
                                                        <div id="emotions-<?php echo $message['id']; ?>" class="collapse mt-2">
                                                            <div class="row g-2">
                                                                <?php
                                                                $emotionIcons = [
                                                                    'joy' => '😊', 'sadness' => '😢', 'fear' => '😨', 'anger' => '😠',
                                                                    'surprise' => '😲', 'disgust' => '🤢', 'trust' => '🤝', 'anticipation' => '🤔',
                                                                    'shame' => '😳', 'love' => '❤️', 'contempt' => '😤', 'loneliness' => '😔',
                                                                    'pride' => '😌', 'envy' => '😏', 'nostalgia' => '🥺', 'gratitude' => '🙏',
                                                                    'frustration' => '😤', 'boredom' => '😴'
                                                                ];
                                                                ?>
                                                                <?php foreach ($emotions as $emotion): ?>
                                                                    <?php if (isset($message["aei_$emotion"]) && $message["aei_$emotion"] !== null): ?>
                                                                        <?php
                                                                        $value = floatval($message["aei_$emotion"]);
                                                                        $percentage = $value * 100;
                                                                        $intensityClass = '';
                                                                        if ($value >= 0.7) $intensityClass = 'text-danger fw-bold';
                                                                        elseif ($value >= 0.5) $intensityClass = 'text-warning';
                                                                        else $intensityClass = 'text-muted';
                                                                        ?>
                                                                        <div class="col-6 col-md-4 col-lg-3">
                                                                            <div class="small d-flex align-items-center">
                                                                                <span class="me-1"><?php echo $emotionIcons[$emotion] ?? '🔘'; ?></span>
                                                                                <span class="<?php echo $intensityClass; ?>">
                                                                                    <?php echo ucfirst($emotion); ?>: <?php echo number_format($value, 1); ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="progress" style="height: 4px;">
                                                                                <div class="progress-bar bg-<?php echo $value >= 0.7 ? 'danger' : ($value >= 0.5 ? 'warning' : 'secondary'); ?>" 
                                                                                     style="width: <?php echo $percentage; ?>%"></div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
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
                    <tr>
                        <td><strong>Rated Messages:</strong></td>
                        <td><?php echo $ratingStats['rated_messages']; ?> / <?php echo $ratingStats['total_messages']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>👍 Thumbs Up:</strong></td>
                        <td><span class="badge bg-success"><?php echo $ratingStats['total_thumbs_up']; ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>👎 Thumbs Down:</strong></td>
                        <td><span class="badge bg-danger"><?php echo $ratingStats['total_thumbs_down']; ?></span></td>
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
        
        <!-- Emotional Evolution Card -->
        <?php
        // Get AEI messages with emotional data for evolution display
        $aeiMessagesWithEmotions = [];
        foreach ($messages as $msg) {
            if ($msg['character_type'] === 'AEI' && isset($msg['aei_joy']) && $msg['aei_joy'] !== null) {
                $aeiMessagesWithEmotions[] = $msg;
            }
        }
        ?>
        
        <?php if (!empty($aeiMessagesWithEmotions)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-brain"></i> Emotional Evolution</h6>
            </div>
            <div class="card-body">
                <div class="small mb-2">
                    AEI Character's emotional journey through the conversation:
                </div>
                
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $emotionIcons = [
                        'joy' => '😊', 'sadness' => '😢', 'fear' => '😨', 'anger' => '😠',
                        'surprise' => '😲', 'disgust' => '🤢', 'trust' => '🤝', 'anticipation' => '🤔',
                        'shame' => '😳', 'love' => '❤️', 'contempt' => '😤', 'loneliness' => '😔',
                        'pride' => '😌', 'envy' => '😏', 'nostalgia' => '🥺', 'gratitude' => '🙏',
                        'frustration' => '😤', 'boredom' => '😴'
                    ];
                    
                    foreach ($aeiMessagesWithEmotions as $index => $msg):
                        // Find the top 3 emotions for this message
                        $msgEmotions = [];
                        foreach (['joy', 'sadness', 'fear', 'anger', 'surprise', 'disgust', 'trust', 'anticipation', 'shame', 'love', 'contempt', 'loneliness', 'pride', 'envy', 'nostalgia', 'gratitude', 'frustration', 'boredom'] as $emotion) {
                            if (isset($msg["aei_$emotion"]) && $msg["aei_$emotion"] !== null) {
                                $msgEmotions[$emotion] = floatval($msg["aei_$emotion"]);
                            }
                        }
                        arsort($msgEmotions);
                        $topEmotions = array_slice($msgEmotions, 0, 3, true);
                    ?>
                    
                    <div class="mb-2 p-2 border-start border-success border-3" style="background-color: #f8f9fa;">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="fw-bold">Turn <?php echo $msg['turn_number']; ?></small>
                            <small class="text-muted"><?php echo date('H:i', strtotime($msg['created_at'])); ?></small>
                        </div>
                        
                        <div class="mt-1">
                            <?php foreach ($topEmotions as $emotion => $value): ?>
                                <?php
                                $intensityClass = '';
                                if ($value >= 0.7) $intensityClass = 'text-danger fw-bold';
                                elseif ($value >= 0.5) $intensityClass = 'text-warning';
                                else $intensityClass = 'text-muted';
                                ?>
                                <span class="me-2">
                                    <span class="me-1"><?php echo $emotionIcons[$emotion] ?? '🔘'; ?></span>
                                    <span class="<?php echo $intensityClass; ?>" style="font-size: 0.75rem;">
                                        <?php echo ucfirst($emotion); ?> <?php echo number_format($value, 1); ?>
                                    </span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($index > 0): ?>
                            <?php
                            // Show changes from previous message
                            $prevMsg = $aeiMessagesWithEmotions[$index - 1];
                            $changes = [];
                            foreach ($topEmotions as $emotion => $value) {
                                $prevValue = floatval($prevMsg["aei_$emotion"] ?? 0.5);
                                $change = $value - $prevValue;
                                if (abs($change) >= 0.1) {
                                    $changeIcon = $change > 0 ? '↗️' : '↘️';
                                    $changes[] = "$changeIcon " . ucfirst($emotion) . " " . sprintf("%+.1f", $change);
                                }
                            }
                            ?>
                            <?php if (!empty($changes)): ?>
                                <div class="mt-1">
                                    <small class="text-info">
                                        Changes: <?php echo implode(', ', $changes); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Showing top emotions per message with changes from previous turn
                    </small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
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

function toggleEmotions(messageId) {
    const emotionsDiv = document.getElementById('emotions-' + messageId);
    const toggleButton = document.getElementById('emotion-toggle-' + messageId);
    const isVisible = emotionsDiv.classList.contains('show');
    
    if (isVisible) {
        emotionsDiv.classList.remove('show');
        toggleButton.textContent = 'Show Emotions';
    } else {
        emotionsDiv.classList.add('show');
        toggleButton.textContent = 'Hide Emotions';
    }
}

// Rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const ratingButtons = document.querySelectorAll('.rating-btn');
    
    ratingButtons.forEach(button => {
        button.addEventListener('click', function() {
            const messageId = this.getAttribute('data-message-id');
            const ratingType = this.getAttribute('data-rating-type');
            
            // Disable button during request
            this.disabled = true;
            
            // Send AJAX request
            fetch('rate_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message_id: parseInt(messageId),
                    rating_type: ratingType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update both buttons for this message
                    const messageContainer = this.closest('.btn-group');
                    const upButton = messageContainer.querySelector('[data-rating-type="up"]');
                    const downButton = messageContainer.querySelector('[data-rating-type="down"]');
                    const upCountSpan = upButton.querySelector('.rating-count-up');
                    const downCountSpan = downButton.querySelector('.rating-count-down');
                    
                    // Update counts
                    upCountSpan.textContent = data.thumbs_up > 0 ? data.thumbs_up : '';
                    downCountSpan.textContent = data.thumbs_down > 0 ? data.thumbs_down : '';
                    
                    // Update button styles
                    if (data.thumbs_up > 0) {
                        upButton.style.backgroundColor = '#198754';
                        upButton.style.color = 'white';
                        upButton.style.borderColor = '#198754';
                    } else {
                        upButton.style.backgroundColor = '';
                        upButton.style.color = '';
                        upButton.style.borderColor = '';
                    }
                    
                    if (data.thumbs_down > 0) {
                        downButton.style.backgroundColor = '#dc3545';
                        downButton.style.color = 'white';
                        downButton.style.borderColor = '#dc3545';
                    } else {
                        downButton.style.backgroundColor = '';
                        downButton.style.color = '';
                        downButton.style.borderColor = '';
                    }
                    
                    // Update statistics in sidebar (reload page to get updated stats)
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                    
                } else {
                    alert('Error: ' + (data.error || 'Failed to rate message'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred while rating message');
            })
            .finally(() => {
                // Re-enable button
                this.disabled = false;
            });
        });
    });
});

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