<?php
require_once 'includes/bootstrap.php';

// Require authentication
requireAuth();

// Get parameters from URL
$messageId = intval($_GET['message_id'] ?? 0);
$dialogId = intval($_GET['dialog_id'] ?? 0);
$bulk = isset($_GET['bulk']) && $_GET['bulk'] === '1';

if (!$messageId && !$dialogId) {
    http_response_code(400);
    echo "Message ID or Dialog ID is required";
    exit;
}

try {
    if ($bulk && $dialogId) {
        // Bulk download all messages from a dialog
        $sql = "SELECT dm.*, d.name as dialog_name, c.name as character_name, c.type as character_type
                FROM dialog_messages dm
                JOIN dialogs d ON dm.dialog_id = d.id
                JOIN characters c ON dm.character_id = c.id
                WHERE dm.dialog_id = ? AND dm.anthropic_request_json IS NOT NULL
                ORDER BY dm.turn_number ASC";
        
        $messages = $database->fetchAll($sql, [$dialogId]);
        
        if (empty($messages)) {
            http_response_code(404);
            echo "No messages with Anthropic request data found";
            exit;
        }
        
        // Check permissions for dialog
        $dialogData = $dialog->getById($dialogId);
        if (!$dialogData) {
            http_response_code(404);
            echo "Dialog not found";
            exit;
        }
        
        if (!$user->isAdmin() && $dialogData['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        // Create ZIP file
        $zipFileName = sprintf(
            "anthropic_requests_dialog_%d_%s.zip",
            $dialogId,
            date('Y-m-d_H-i-s')
        );
        
        // Create temporary directory
        $tempDir = sys_get_temp_dir() . '/anthropic_requests_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            http_response_code(500);
            echo "Failed to create temporary directory";
            exit;
        }
        
        // Create individual JSON files
        foreach ($messages as $message) {
            $requestData = json_decode($message['anthropic_request_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            
            $fileData = [
                'metadata' => [
                    'dialog_id' => $message['dialog_id'],
                    'dialog_name' => $message['dialog_name'],
                    'message_id' => $message['id'],
                    'turn_number' => $message['turn_number'],
                    'character_name' => $message['character_name'],
                    'character_type' => $message['character_type'],
                    'message_created_at' => $message['created_at']
                ],
                'request_data' => $requestData
            ];
            
            $fileName = sprintf(
                "turn_%02d_%s_%s.json",
                $message['turn_number'],
                $message['character_type'],
                $message['character_name']
            );
            
            file_put_contents(
                $tempDir . '/' . $fileName,
                json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
        
        // Create ZIP
        $zip = new ZipArchive();
        $zipPath = $tempDir . '/' . $zipFileName;
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            http_response_code(500);
            echo "Failed to create ZIP file";
            exit;
        }
        
        // Add files to ZIP
        $files = scandir($tempDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== $zipFileName) {
                $zip->addFile($tempDir . '/' . $file, $file);
            }
        }
        
        $zip->close();
        
        // Send ZIP file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($zipPath);
        
        // Clean up
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        
        exit;
    } else {
        // Single message download
        $sql = "SELECT dm.*, d.name as dialog_name, c.name as character_name, c.type as character_type
                FROM dialog_messages dm
                JOIN dialogs d ON dm.dialog_id = d.id
                JOIN characters c ON dm.character_id = c.id
                WHERE dm.id = ?";
        
        $message = $database->fetch($sql, [$messageId]);
    }
    
    if (!$message) {
        http_response_code(404);
        echo "Message not found";
        exit;
    }
    
    // Check if user has permission to access this dialog
    $dialogData = $dialog->getById($message['dialog_id']);
    if (!$dialogData) {
        http_response_code(404);
        echo "Dialog not found";
        exit;
    }
    
    // Check permissions
    if (!$user->isAdmin() && $dialogData['created_by'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    
    // Check if anthropic request data exists
    if (!$message['anthropic_request_json']) {
        http_response_code(404);
        echo "No Anthropic request data found for this message";
        exit;
    }
    
    // Prepare filename
    $filename = sprintf(
        "anthropic_request_dialog_%d_turn_%d_%s.json",
        $message['dialog_id'],
        $message['turn_number'],
        date('Y-m-d_H-i-s', strtotime($message['created_at']))
    );
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Parse and pretty print JSON
    $requestData = json_decode($message['anthropic_request_json'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo "Error parsing JSON data";
        exit;
    }
    
    // Add metadata
    $downloadData = [
        'metadata' => [
            'downloaded_at' => date('Y-m-d H:i:s'),
            'downloaded_by' => $_SESSION['username'],
            'dialog_id' => $message['dialog_id'],
            'dialog_name' => $message['dialog_name'],
            'message_id' => $messageId,
            'turn_number' => $message['turn_number'],
            'character_name' => $message['character_name'],
            'character_type' => $message['character_type'],
            'message_created_at' => $message['created_at']
        ],
        'request_data' => $requestData
    ];
    
    // Output the JSON
    echo json_encode($downloadData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Anthropic request download error: " . $e->getMessage());
    echo "Internal server error";
}
?> 