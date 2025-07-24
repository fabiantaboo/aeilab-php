<?php
/**
 * Test Emotion Analysis Script
 * Tests the emotion analysis functionality with a sample dialog
 */

require_once 'includes/bootstrap.php';

echo "=== Emotion Analysis Test ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get a test dialog with messages
    $testDialog = $database->fetch("SELECT * FROM dialogs WHERE id IN (SELECT DISTINCT dialog_id FROM dialog_messages) LIMIT 1");
    if (!$testDialog) {
        echo "❌ No dialog with messages found. Please create a dialog and let it run first.\n";
        exit(1);
    }
    
    echo "Found test dialog: {$testDialog['name']} (ID: {$testDialog['id']})\n";
    
    // Get dialog messages
    $messages = $dialog->getMessages($testDialog['id']);
    echo "Dialog has " . count($messages) . " messages\n\n";
    
    if (empty($messages)) {
        echo "❌ No messages found in dialog.\n";
        exit(1);
    }
    
    // Get AEI character name
    $aeiCharacter = $database->fetch("SELECT name FROM characters WHERE id = ?", [$testDialog['aei_character_id']]);
    $aeiCharacterName = $aeiCharacter ? $aeiCharacter['name'] : 'AEI Character';
    
    echo "=== Testing Emotion Analysis ===\n";
    echo "AEI Character: $aeiCharacterName\n";
    echo "Topic: {$testDialog['topic']}\n\n";
    
    // Initialize Anthropic API
    $anthropicAPI = new AnthropicAPI(defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : null);
    
    // Test emotion analysis
    echo "Calling analyzeEmotionalState...\n";
    $emotionAnalysis = $anthropicAPI->analyzeEmotionalState(
        $messages,
        $aeiCharacterName,
        $testDialog['topic']
    );
    
    echo "\n=== Results ===\n";
    if ($emotionAnalysis['success']) {
        echo "✅ Emotion analysis successful!\n\n";
        
        echo "Analyzed emotions:\n";
        foreach ($emotionAnalysis['emotions'] as $emotion => $value) {
            $bar = str_repeat('█', round($value * 10));
            $bar = str_pad($bar, 10, '░');
            echo sprintf("%-12s: %.1f [$bar]\n", ucfirst($emotion), $value);
        }
        
        echo "\nRaw API Response:\n";
        echo "================\n";
        echo $emotionAnalysis['raw_response'] . "\n";
        
    } else {
        echo "❌ Emotion analysis failed!\n";
        echo "Error: " . ($emotionAnalysis['error'] ?? 'Unknown error') . "\n";
        echo "Raw response: " . ($emotionAnalysis['raw_response'] ?? 'No response') . "\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>