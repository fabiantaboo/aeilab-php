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
$message = trim($_POST['message'] ?? '');

// Validate input
if (!$characterId || !$chatSessionId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Get character data
$characterData = $character->getById($characterId);
if (!$characterData || !$characterData['is_active'] || $characterData['type'] !== 'AEI') {
    echo json_encode(['success' => false, 'error' => 'Invalid character']);
    exit;
}

try {
    // Get chat history from session
    $chatHistory = $_SESSION['chat_messages_' . $chatSessionId] ?? [];
    
    // Add user message to history
    $userMessage = [
        'sender' => 'user',
        'content' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $chatHistory[] = $userMessage;
    
    // Prepare conversation context for AI
    $conversationContext = [];
    
    // Add system prompt
    $conversationContext[] = [
        'role' => 'system',
        'content' => $characterData['system_prompt']
    ];
    
    // Add recent chat history (limit to last 10 messages to stay within token limits)
    $recentHistory = array_slice($chatHistory, -10);
    foreach ($recentHistory as $historyMessage) {
        if ($historyMessage['sender'] === 'user') {
            $conversationContext[] = [
                'role' => 'user',
                'content' => $historyMessage['content']
            ];
        } else {
            $conversationContext[] = [
                'role' => 'assistant',
                'content' => $historyMessage['content']
            ];
        }
    }
    
    // Get AI response using AnthropicAPI
    $api = new AnthropicAPI($db);
    $response = $api->generateResponse($conversationContext);
    
    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to generate AI response']);
        exit;
    }
    
    // Add AI response to history
    $aiMessage = [
        'sender' => 'ai',
        'content' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $chatHistory[] = $aiMessage;
    
    // Save updated chat history to session
    $_SESSION['chat_messages_' . $chatSessionId] = $chatHistory;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'response' => $response,
        'character_name' => $characterData['name']
    ]);
    
} catch (Exception $e) {
    error_log("Chat message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>