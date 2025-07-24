<?php
/**
 * Debug Emotions Display Script
 * Checks if emotional data is properly stored and displayed
 */

require_once 'includes/bootstrap.php';

echo "=== Debug Emotions Display ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Check dialog_messages table structure
    echo "1. Checking dialog_messages table structure:\n";
    echo "==========================================\n";
    
    $columns = $database->fetchAll("SHOW COLUMNS FROM dialog_messages");
    $emotionalColumns = [];
    
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'aei_') === 0) {
            $emotionalColumns[] = $column['Field'];
        }
        printf("%-25s %-15s %-8s\n", $column['Field'], $column['Type'], $column['Null']);
    }
    
    echo "\nEmotional columns found: " . count($emotionalColumns) . "\n";
    if (!empty($emotionalColumns)) {
        echo "Columns: " . implode(', ', $emotionalColumns) . "\n";
    }
    
    // 2. Check for messages with emotional data
    echo "\n2. Checking messages with emotional data:\n";
    echo "========================================\n";
    
    $emotionalFields = implode(', ', $emotionalColumns);
    if (!empty($emotionalFields)) {
        $query = "SELECT id, dialog_id, character_id, turn_number, $emotionalFields FROM dialog_messages WHERE aei_joy IS NOT NULL LIMIT 5";
        $messagesWithEmotions = $database->fetchAll($query);
        
        echo "Messages with emotional data: " . count($messagesWithEmotions) . "\n";
        
        foreach ($messagesWithEmotions as $msg) {
            echo "\nMessage ID {$msg['id']} (Dialog {$msg['dialog_id']}, Turn {$msg['turn_number']}):\n";
            foreach ($emotionalColumns as $col) {
                if ($msg[$col] !== null) {
                    $emotion = str_replace('aei_', '', $col);
                    echo "  $emotion: {$msg[$col]}\n";
                }
            }
        }
    } else {
        echo "❌ No emotional columns found in dialog_messages table!\n";
    }
    
    // 3. Check specific dialog
    echo "\n3. Checking specific dialog data:\n";
    echo "================================\n";
    
    $testDialog = $database->fetch("SELECT * FROM dialogs LIMIT 1");
    if ($testDialog) {
        echo "Test Dialog: {$testDialog['name']} (ID: {$testDialog['id']})\n";
        
        // Get messages for this dialog
        $messages = $dialog->getMessages($testDialog['id']);
        echo "Messages in dialog: " . count($messages) . "\n";
        
        foreach ($messages as $message) {
            echo "\nMessage {$message['id']} ({$message['character_type']}):\n";
            echo "  Character: {$message['character_name']}\n";
            echo "  Turn: {$message['turn_number']}\n";
            
            // Check for emotional data
            $hasEmotions = false;
            $emotions = ['joy', 'sadness', 'fear', 'anger', 'surprise', 'disgust',
                        'trust', 'anticipation', 'shame', 'love', 'contempt', 
                        'loneliness', 'pride', 'envy', 'nostalgia', 'gratitude',
                        'frustration', 'boredom'];
            
            foreach ($emotions as $emotion) {
                $key = "aei_$emotion";
                if (isset($message[$key]) && $message[$key] !== null) {
                    if (!$hasEmotions) {
                        echo "  Emotions:\n";
                        $hasEmotions = true;
                    }
                    echo "    $emotion: {$message[$key]}\n";
                }
            }
            
            if (!$hasEmotions) {
                echo "  ❌ No emotional data found\n";
            }
        }
    }
    
    // 4. Test dialog view query
    echo "\n4. Testing dialog view query:\n";
    echo "============================\n";
    
    if ($testDialog) {
        $sql = "SELECT dm.*, c.name as character_name, c.type as character_type
                FROM dialog_messages dm
                JOIN characters c ON dm.character_id = c.id
                WHERE dm.dialog_id = ?
                ORDER BY dm.turn_number ASC, dm.created_at ASC";
        
        $viewMessages = $database->fetchAll($sql, [$testDialog['id']]);
        
        echo "Messages from view query: " . count($viewMessages) . "\n";
        
        foreach ($viewMessages as $msg) {
            echo "\nMessage {$msg['id']} - Fields available:\n";
            $emotionFields = 0;
            foreach ($msg as $key => $value) {
                if (strpos($key, 'aei_') === 0 && $value !== null) {
                    $emotionFields++;
                    echo "  $key: $value\n";
                }
            }
            if ($emotionFields === 0) {
                echo "  ❌ No emotion fields with data\n";
            }
        }
    }
    
    echo "\n=== Debug completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>