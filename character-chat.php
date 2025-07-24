<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

$characterId = intval($_GET['character_id'] ?? 0);
if (!$characterId) {
    header('Location: characters.php');
    exit;
}

$characterData = $character->getById($characterId);
if (!$characterData || !$characterData['is_active']) {
    header('Location: characters.php');
    exit;
}

// Only allow chatting with AEI characters
if ($characterData['type'] !== 'AEI') {
    $_SESSION['error'] = 'You can only chat with AEI characters.';
    header('Location: character-view.php?id=' . $characterId);
    exit;
}

// Create a unique session ID for this chat
$chatSessionId = $_SESSION['chat_session_' . $characterId] ?? uniqid('chat_');
$_SESSION['chat_session_' . $characterId] = $chatSessionId;

// Get chat history from session
$chatHistory = $_SESSION['chat_messages_' . $chatSessionId] ?? [];

includeHeader('Chat with ' . $characterData['name'] . ' - AEI Lab');
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="fas fa-comments"></i> 
                    Chat with <?php echo htmlspecialchars($characterData['name']); ?>
                    <span class="badge bg-success ms-2">AEI Character</span>
                </h5>
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearChat()">
                        <i class="fas fa-trash"></i> Clear Chat
                    </button>
                    <a href="character-view.php?id=<?php echo $characterId; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Character
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Chat Messages Area -->
                <div id="chat-messages" class="chat-messages" style="height: 500px; overflow-y: auto; padding: 20px;">
                    <?php if (empty($chatHistory)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <h5>Start a conversation</h5>
                            <p>Send a message to begin chatting with <?php echo htmlspecialchars($characterData['name']); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chatHistory as $index => $message): ?>
                            <div class="message mb-3 <?php echo $message['sender'] === 'user' ? 'user-message' : 'ai-message'; ?>">
                                <div class="d-flex <?php echo $message['sender'] === 'user' ? 'justify-content-end' : 'justify-content-start'; ?>">
                                    <div class="message-bubble p-3 rounded-3 <?php echo $message['sender'] === 'user' ? 'bg-primary text-white' : 'bg-light'; ?>" style="max-width: 70%;">
                                        <div class="message-header mb-2">
                                            <strong><?php echo $message['sender'] === 'user' ? 'You' : htmlspecialchars($characterData['name']); ?></strong>
                                            <small class="text-muted ms-2"><?php echo date('H:i:s', strtotime($message['timestamp'])); ?></small>
                                        </div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Input -->
                <div class="chat-input border-top p-3">
                    <div class="d-flex gap-2">
                        <input type="hidden" id="character-id" value="<?php echo $characterId; ?>">
                        <input type="hidden" id="chat-session-id" value="<?php echo $chatSessionId; ?>">
                        <div class="flex-grow-1">
                            <textarea id="message-input" class="form-control" placeholder="Type your message here..." rows="2"></textarea>
                        </div>
                        <div class="d-flex flex-column gap-1">
                            <button type="button" class="btn btn-primary" id="send-btn" onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="saveDialog()">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-robot"></i> Character Information</h6>
            </div>
            <div class="card-body">
                <h5><?php echo htmlspecialchars($characterData['name']); ?></h5>
                <?php if ($characterData['description']): ?>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($characterData['description'])); ?></p>
                <?php endif; ?>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Type:</strong></td>
                        <td><span class="badge bg-success"><?php echo htmlspecialchars($characterData['type']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Creator:</strong></td>
                        <td><?php echo htmlspecialchars($characterData['creator_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo date('M j, Y', strtotime($characterData['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-cog"></i> Chat Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success" onclick="saveDialog()">
                        <i class="fas fa-save"></i> Save as Dialog
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="exportChat()">
                        <i class="fas fa-download"></i> Export Chat
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearChat()">
                        <i class="fas fa-trash"></i> Clear Chat
                    </button>
                    <a href="character-view.php?id=<?php echo $characterId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-eye"></i> View Character
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Chat Tips</h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <ul class="mb-0">
                        <li>Press Ctrl+Enter to send your message</li>
                        <li>Use the Save button to create a permanent dialog</li>
                        <li>Export your chat for external use</li>
                        <li>Clear chat to start a fresh conversation</li>
                    </ul>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Generating response...</p>
            </div>
        </div>
    </div>
</div>

<style>
.chat-messages {
    background-color: #f8f9fa;
}

.message-bubble {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    word-wrap: break-word;
}

.user-message .message-bubble {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.ai-message .message-bubble {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
}

.chat-input {
    background-color: #ffffff;
}

.message-header strong {
    font-size: 0.9rem;
}

.message-header small {
    font-size: 0.75rem;
    opacity: 0.7;
}

#message-input {
    resize: none;
    border-radius: 20px;
}

#message-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available
    if (typeof $ === 'undefined') {
        setTimeout(function() {
            initializeChat();
        }, 100);
    } else {
        initializeChat();
    }
});

function initializeChat() {
    // Scroll to bottom of chat
    scrollToBottom();
    
    // Focus on input
    $('#message-input').focus();
    
    // Handle Enter key (without Shift = send, with Shift = new line)
    $('#message-input').on('keydown', function(e) {
        if (e.keyCode === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Auto-resize textarea
    $('#message-input').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

function sendMessage() {
    const messageInput = $('#message-input');
    const message = messageInput.val().trim();
    
    if (!message) {
        return;
    }
    
    // Disable send button and show loading
    $('#send-btn').prop('disabled', true);
    $('#loadingModal').modal('show');
    
    // Add user message to chat immediately
    addMessageToChat('user', message, 'You');
    messageInput.val('');
    scrollToBottom();
    
    // Send message to server
    $.ajax({
        url: 'ajax/chat_message.php',
        type: 'POST',
        dataType: 'json',
        data: {
            character_id: $('#character-id').val(),
            chat_session_id: $('#chat-session-id').val(),
            message: message,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('AJAX Response:', response);
            if (response.success) {
                // Add AI response to chat
                addMessageToChat('ai', response.response, '<?php echo addslashes($characterData['name']); ?>');
                scrollToBottom();
            } else {
                showError(response.error || 'Failed to send message');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showError('Network error occurred: ' + error);
        },
        complete: function() {
            $('#send-btn').prop('disabled', false);
            $('#loadingModal').modal('hide');
            $('#message-input').focus();
        }
    });
}

function addMessageToChat(sender, content, senderName) {
    const timestamp = new Date().toLocaleTimeString();
    const isUser = sender === 'user';
    
    const messageHtml = `
        <div class="message mb-3 ${isUser ? 'user-message' : 'ai-message'}">
            <div class="d-flex ${isUser ? 'justify-content-end' : 'justify-content-start'}">
                <div class="message-bubble p-3 rounded-3 ${isUser ? 'bg-primary text-white' : 'bg-light'}" style="max-width: 70%;">
                    <div class="message-header mb-2">
                        <strong>${senderName}</strong>
                        <small class="text-muted ms-2">${timestamp}</small>
                    </div>
                    <div class="message-content">
                        ${content.replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#chat-messages').append(messageHtml);
}

function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function clearChat() {
    if (!confirm('Are you sure you want to clear the chat history? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/clear_chat.php',
        type: 'POST',
        dataType: 'json',
        data: {
            character_id: $('#character-id').val(),
            chat_session_id: $('#chat-session-id').val(),
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#chat-messages').html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <h5>Start a conversation</h5>
                        <p>Send a message to begin chatting with <?php echo addslashes($characterData['name']); ?></p>
                    </div>
                `);
                showSuccess('Chat cleared successfully');
            } else {
                showError(response.error || 'Failed to clear chat');
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function saveDialog() {
    const dialogName = prompt('Enter a name for this dialog:', 'Chat with <?php echo addslashes($characterData['name']); ?> - ' + new Date().toLocaleDateString());
    
    if (!dialogName) {
        return;
    }
    
    $.ajax({
        url: 'ajax/save_chat_dialog.php',
        type: 'POST',
        dataType: 'json',
        data: {
            character_id: $('#character-id').val(),
            chat_session_id: $('#chat-session-id').val(),
            dialog_name: dialogName,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showSuccess('Dialog saved successfully! <a href="dialog-view.php?id=' + response.dialog_id + '" class="alert-link">View Dialog</a>');
            } else {
                showError(response.error || 'Failed to save dialog');
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function exportChat() {
    window.open('ajax/export_chat.php?character_id=' + $('#character-id').val() + '&chat_session_id=' + $('#chat-session-id').val(), '_blank');
}

function showSuccess(message) {
    // Create and show success alert
    const alert = $(`
        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('body').append(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.fadeOut(() => alert.remove());
    }, 5000);
}

function showError(message) {
    // Create and show error alert
    const alert = $(`
        <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('body').append(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.fadeOut(() => alert.remove());
    }, 5000);
}
</script>

<?php includeFooter(); ?>