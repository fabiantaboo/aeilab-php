<?php
require_once 'includes/bootstrap.php';

echo "=== Database Fix Script ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Check if anthropic_request_json column exists
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
    
    // 2. Check if retry_count column exists in dialog_jobs
    echo "\n--- Checking retry_count column ---\n";
    $retryColumnCheck = $database->query("SHOW COLUMNS FROM dialog_jobs LIKE 'retry_count'");
    $retryColumnExists = $retryColumnCheck->rowCount() > 0;
    
    if (!$retryColumnExists) {
        echo "Adding retry_count column to dialog_jobs table...\n";
        $database->query("ALTER TABLE dialog_jobs ADD COLUMN retry_count INT DEFAULT 0 AFTER error_message");
        echo "✅ retry_count column added successfully!\n";
    } else {
        echo "✅ retry_count column already exists.\n";
    }
    
    // 3. Reset all failed jobs to pending for immediate retry
    echo "\n--- Resetting failed jobs ---\n";
    $resetCount = $database->query(
        "UPDATE dialog_jobs SET status = 'pending', error_message = 'Reset for improved retry', retry_count = COALESCE(retry_count, 0) WHERE status = 'failed'"
    )->rowCount();
    
    echo "✅ Reset $resetCount failed jobs to pending\n";
    
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
    
    // 4. Show current job statistics
    echo "\n--- Current Job Statistics ---\n";
    $stats = $dialogJob->getStats();
    foreach ($stats as $status => $count) {
        echo "$status: $count\n";
    }
    
    echo "\n✅ Database fix completed successfully!\n";
    echo "The retry mechanism is now improved with:\n";
    echo "- Rate limit detection (429, 529, overload errors)\n";
    echo "- Exponential backoff (rate limits: 5min, others: 2min)\n";
    echo "- Retry count tracking (max 5 for rate limits, 3 for others)\n";
    echo "- Automatic job reset for immediate processing\n\n";
    
    echo "=== Fix completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 