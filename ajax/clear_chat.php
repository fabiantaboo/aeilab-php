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

// Validate input
if (!$characterId || !$chatSessionId) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Clear chat history from session
    unset($_SESSION['chat_messages_' . $chatSessionId]);
    
    // Also clear the chat session ID so a new one will be generated
    unset($_SESSION['chat_session_' . $characterId]);
    
    // Return success response with instruction to reload page
    echo json_encode(['success' => true, 'reload' => true]);
    
} catch (Exception $e) {
    error_log("Clear chat error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>