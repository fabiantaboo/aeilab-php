<?php
/**
 * Backfill Emotions Script
 * Analyzes existing AEI messages and adds emotional data retroactively
 */

require_once 'includes/bootstrap.php';

echo "=== Backfill Emotions Script ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialize Anthropic API
    $anthropicAPI = new AnthropicAPI(defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : null);
    
    // Get all dialogs with AEI messages that don't have emotional data
    $sql = "SELECT DISTINCT dm.dialog_id, d.name, d.topic, 
                   aei_char.name as aei_character_name
            FROM dialog_messages dm
            JOIN dialogs d ON dm.dialog_id = d.id
            JOIN characters aei_char ON d.aei_character_id = aei_char.id
            JOIN characters msg_char ON dm.character_id = msg_char.id
            WHERE msg_char.type = 'AEI' 
            AND dm.aei_joy IS NULL
            ORDER BY dm.dialog_id";
    
    $dialogsToProcess = $database->fetchAll($sql);
    
    echo "Found " . count($dialogsToProcess) . " dialogs with AEI messages needing emotional data\n\n";
    
    foreach ($dialogsToProcess as $dialogInfo) {
        $dialogId = $dialogInfo['dialog_id'];
        $dialogName = $dialogInfo['name'];
        $topic = $dialogInfo['topic'];
        $aeiCharacterName = $dialogInfo['aei_character_name'];
        
        echo "Processing Dialog $dialogId: '$dialogName'\n";
        echo "  Topic: $topic\n";
        echo "  AEI Character: $aeiCharacterName\n";
        
        // Get all messages for this dialog
        $messages = $dialog->getMessages($dialogId);
        
        if (empty($messages)) {
            echo "  ❌ No messages found, skipping\n\n";
            continue;
        }
        
        // Get current emotional state from dialog table
        $currentEmotions = $dialog->getEmotionalState($dialogId);
        if (!$currentEmotions) {
            echo "  ❌ No emotional state in dialog table, skipping\n\n";
            continue;
        }
        
        echo "  Messages: " . count($messages) . "\n";
        
        // Process each AEI message
        $processedCount = 0;
        foreach ($messages as $message) {
            if ($message['character_type'] !== 'AEI') {
                continue; // Skip User messages
            }
            
            // Check if this message already has emotional data
            if ($message['aei_joy'] !== null) {
                continue; // Skip messages that already have emotions
            }
            
            echo "    Processing AEI message {$message['id']} (Turn {$message['turn_number']})...\n";
            
            try {
                // Get conversation history up to this point
                $historyUpToPoint = [];
                foreach ($messages as $historyMsg) {
                    if ($historyMsg['turn_number'] <= $message['turn_number']) {
                        $historyUpToPoint[] = $historyMsg;
                    }
                }
                
                // Analyze emotional state for this specific point in conversation
                $emotionAnalysis = $anthropicAPI->analyzeEmotionalState(
                    $historyUpToPoint,
                    $aeiCharacterName,
                    $topic
                );
                
                if ($emotionAnalysis['success']) {
                    // Update this message with the analyzed emotions
                    $emotionUpdates = [];
                    $emotionParams = [];
                    
                    foreach (Dialog::EMOTIONS as $emotion) {
                        $emotionUpdates[] = "aei_$emotion = ?";
                        $value = $emotionAnalysis['emotions'][$emotion] ?? 0.5;
                        $emotionParams[] = max(0, min(1, round($value, 1)));
                    }
                    $emotionParams[] = $message['id'];
                    
                    $updateSql = "UPDATE dialog_messages SET " . implode(', ', $emotionUpdates) . " WHERE id = ?";
                    $database->query($updateSql, $emotionParams);
                    
                    echo "      ✅ Updated with emotions\n";
                    $processedCount++;
                    
                    // Add a small delay to avoid rate limiting
                    sleep(1);
                    
                } else {
                    echo "      ❌ Emotion analysis failed: " . ($emotionAnalysis['error'] ?? 'Unknown error') . "\n";
                }
                
            } catch (Exception $e) {
                echo "      ❌ Error processing message: " . $e->getMessage() . "\n";
            }
        }
        
        echo "  ✅ Processed $processedCount AEI messages\n\n";
    }
    
    // Summary
    echo "\n=== Summary ===\n";
    $updatedCount = $database->fetch("SELECT COUNT(*) as count FROM dialog_messages WHERE aei_joy IS NOT NULL")['count'];
    echo "Total messages with emotional data: $updatedCount\n";
    
    echo "\n=== Backfill completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>