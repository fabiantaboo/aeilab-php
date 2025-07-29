<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

// Ensure global variables are available
global $db, $dialog, $user;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $messageId = intval($input['message_id'] ?? 0);
    $ratingType = $input['rating_type'] ?? '';
    
    if (!$messageId) {
        throw new Exception('Message ID is required');
    }
    
    if (!in_array($ratingType, ['up', 'down'])) {
        throw new Exception('Invalid rating type');
    }
    
    // Verify that the message exists and get its dialog_id
    $messageData = $db->fetch("SELECT dm.id, dm.dialog_id FROM dialog_messages dm WHERE dm.id = ?", [$messageId]);
    
    if (!$messageData) {
        throw new Exception('Message not found');
    }
    
    // Check if user can access this dialog (admin or creator)
    $dialogData = $dialog->getById($messageData['dialog_id']);
    
    if (!$dialogData || (!$user->isAdmin() && $dialogData['created_by'] != $_SESSION['user_id'])) {
        throw new Exception('Access denied');
    }
    
    // Ensure rating columns exist
    $dialog->ensureRatingColumns();
    
    // Rate the message
    $success = $dialog->rateMessage($messageId, $ratingType);
    
    if (!$success) {
        throw new Exception('Failed to rate message');
    }
    
    // Get updated message data to return current ratings
    $updatedMessage = $db->fetch("SELECT rating_thumbs_up, rating_thumbs_down FROM dialog_messages WHERE id = ?", [$messageId]);
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'thumbs_up' => intval($updatedMessage['rating_thumbs_up']),
        'thumbs_down' => intval($updatedMessage['rating_thumbs_down'])
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    error_log("Rating error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>