<?php
require_once '../includes/bootstrap.php';

// Require authentication
requireAuth();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get POST data
$characterId = intval($_POST['character_id'] ?? 0);
$chatSessionId = $_POST['chat_session_id'] ?? '';
$dialogName = trim($_POST['dialog_name'] ?? '');

// Validate input
if (!$characterId || !$chatSessionId || !$dialogName) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Get character data
$characterData = $character->getById($characterId);
if (!$characterData || !$characterData['is_active'] || $characterData['type'] !== 'AEI') {
    echo json_encode(['success' => false, 'error' => 'Invalid character']);
    exit;
}

// Get chat history from session
$chatHistory = $_SESSION['chat_messages_' . $chatSessionId] ?? [];

if (empty($chatHistory)) {
    echo json_encode(['success' => false, 'error' => 'No chat messages to save']);
    exit;
}

try {
    // Create a simple User character for this chat session
    $userCharacterName = 'Chat User (' . date('Y-m-d H:i') . ')';
    $userCharacterPrompt = 'You are a user participating in a manual chat session with an AEI character.';
    
    $userCharacterId = $character->create([
        'name' => $userCharacterName,
        'type' => 'User',
        'system_prompt' => $userCharacterPrompt,
        'description' => 'Auto-generated user character for manual chat session',
        'created_by' => $_SESSION['user_id']
    ]);
    
    if (!$userCharacterId) {
        echo json_encode(['success' => false, 'error' => 'Failed to create user character']);
        exit;
    }
    
    // Create dialog directly in database WITHOUT triggering background jobs
    $sql = "INSERT INTO dialogs (name, description, aei_character_id, user_character_id, topic, turns_per_topic, status, created_by, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $database->query($sql, [
        $dialogName,
        'Manual chat session saved as dialog',
        $characterId,
        $userCharacterId,
        'Manual Chat Session',
        ceil(count($chatHistory) / 2), // Number of turns (user-ai pairs)
        'completed', // Set as completed immediately
        $_SESSION['user_id']
    ]);
    
    $dialogId = $database->lastInsertId();
    
    if (!$dialogId) {
        echo json_encode(['success' => false, 'error' => 'Failed to create dialog']);
        exit;
    }
    
    // Add messages to dialog with proper turn numbering
    // Each user-ai pair should share the same turn number
    $turnNumber = 1;
    foreach ($chatHistory as $index => $message) {
        $messageCharacterId = $message['sender'] === 'ai' ? $characterId : $userCharacterId;
        
        // Calculate turn number: each pair of messages (user + ai) = 1 turn
        $currentTurn = floor($index / 2) + 1;
        
        $success = $dialog->addMessage(
            $dialogId,
            $messageCharacterId,
            $message['content'],
            $currentTurn
        );
        
        if (!$success) {
            error_log("Failed to add message to dialog: " . json_encode($message));
        }
    }
    
    // Clear the chat session
    unset($_SESSION['chat_messages_' . $chatSessionId]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'dialog_id' => $dialogId,
        'dialog_name' => $dialogName
    ]);
    
} catch (Exception $e) {
    error_log("Save chat dialog error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>