<?php
require_once 'includes/bootstrap.php';

echo "=== Database Fix Script ===\n";

try {
    // Check if anthropic_request_json column exists
    $sql = "SHOW COLUMNS FROM dialog_messages LIKE 'anthropic_request_json'";
    $result = $database->query($sql);
    $columnExists = $result->rowCount() > 0;
    
    if (!$columnExists) {
        echo "❌ Column 'anthropic_request_json' does not exist. Adding it...\n";
        
        // Add the column
        $alterSQL = "ALTER TABLE dialog_messages ADD COLUMN anthropic_request_json TEXT NULL AFTER turn_number";
        $database->query($alterSQL);
        
        echo "✅ Column 'anthropic_request_json' added successfully!\n";
    } else {
        echo "✅ Column 'anthropic_request_json' already exists.\n";
    }
    
    // Test the addMessage method
    echo "\n=== Testing addMessage method ===\n";
    
    // Get a test dialog
    $testDialog = $database->fetch("SELECT id FROM dialogs LIMIT 1");
    if (!$testDialog) {
        echo "❌ No test dialog found. Please create a dialog first.\n";
        exit(1);
    }
    
    // Get a test character
    $testCharacter = $database->fetch("SELECT id FROM characters LIMIT 1");
    if (!$testCharacter) {
        echo "❌ No test character found. Please create a character first.\n";
        exit(1);
    }
    
    // Test adding a message
    $testMessage = "Test message for database fix";
    $testTurn = 999; // Use a high number to avoid conflicts
    $testJson = json_encode(['test' => 'data']);
    
    $success = $dialog->addMessage(
        $testDialog['id'],
        $testCharacter['id'],
        $testMessage,
        $testTurn,
        $testJson
    );
    
    if ($success) {
        echo "✅ addMessage test successful!\n";
        
        // Clean up test message
        $database->query("DELETE FROM dialog_messages WHERE turn_number = ? AND message = ?", [$testTurn, $testMessage]);
        echo "✅ Test message cleaned up.\n";
    } else {
        echo "❌ addMessage test failed!\n";
    }
    
    // Show current table structure
    echo "\n=== Current dialog_messages table structure ===\n";
    $columns = $database->fetchAll("SHOW COLUMNS FROM dialog_messages");
    foreach ($columns as $column) {
        echo sprintf("%-25s %-15s %-8s %-8s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key']
        );
    }
    
    echo "\n=== Fix completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 