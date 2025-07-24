<?php
require_once '../includes/bootstrap.php';

// Require authentication
requireAuth();

// Get parameters
$characterId = intval($_GET['character_id'] ?? 0);
$chatSessionId = $_GET['chat_session_id'] ?? '';

// Validate input
if (!$characterId || !$chatSessionId) {
    http_response_code(400);
    die('Invalid parameters');
}

// Get character data
$characterData = $character->getById($characterId);
if (!$characterData || !$characterData['is_active']) {
    http_response_code(404);
    die('Character not found');
}

// Get chat history from session
$chatHistory = $_SESSION['chat_messages_' . $chatSessionId] ?? [];

if (empty($chatHistory)) {
    http_response_code(404);
    die('No chat history found');
}

// Prepare export data
$exportData = [
    'character' => [
        'id' => $characterData['id'],
        'name' => $characterData['name'],
        'type' => $characterData['type'],
        'description' => $characterData['description']
    ],
    'chat_session' => [
        'session_id' => $chatSessionId,
        'exported_at' => date('Y-m-d H:i:s'),
        'message_count' => count($chatHistory)
    ],
    'messages' => $chatHistory
];

// Set headers for JSON download
$filename = 'chat_' . $characterData['name'] . '_' . date('Y-m-d_H-i-s') . '.json';
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen(json_encode($exportData, JSON_PRETTY_PRINT)));

// Output JSON
echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>