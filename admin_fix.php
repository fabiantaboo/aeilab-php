<?php
require_once 'includes/bootstrap.php';

// Require admin access
requireAuth();
if (!$user->isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$output = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_database') {
        ob_start();
        
        try {
            echo "=== Database Fix Script ===\n";
            
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
            } else {
                // Get a test character
                $testCharacter = $database->fetch("SELECT id FROM characters LIMIT 1");
                if (!$testCharacter) {
                    echo "❌ No test character found. Please create a character first.\n";
                } else {
                    // Test adding a message
                    $testMessage = "Test message for database fix";
                    $testTurn = 999; // Use a high number to avoid conflicts
                    $testJson = json_encode(['test' => 'data', 'timestamp' => date('Y-m-d H:i:s')]);
                    
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
                }
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
        }
        
        $output = ob_get_clean();
    }
}

includeHeader('Database Fix - Admin');
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-wrench"></i> Database Fix - Anthropic Request JSON</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Problem:</strong> The Background Processor is failing with "Failed to save message to database" error.
                        <br><br>
                        <strong>Solution:</strong> This script will add the missing <code>anthropic_request_json</code> column to the database and test the functionality.
                    </div>
                    
                    <?php if ($output): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-terminal"></i> Script Output:</h6>
                            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; white-space: pre-wrap;"><?php echo htmlspecialchars($output); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Error:</h6>
                            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($error); ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="fix_database">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-wrench"></i> Run Database Fix
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> What this fix does:</h6>
                            <ul>
                                <li>Adds the <code>anthropic_request_json</code> column to <code>dialog_messages</code> table</li>
                                <li>Tests the <code>addMessage</code> method with the new column</li>
                                <li>Shows the current table structure</li>
                                <li>Provides detailed error information if something fails</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-shield-alt"></i> Safety notes:</h6>
                            <ul>
                                <li>This operation is safe and non-destructive</li>
                                <li>No existing data will be lost</li>
                                <li>The fix is backward compatible</li>
                                <li>Test messages are automatically cleaned up</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="jobs.php" class="btn btn-outline-secondary">
                            <i class="fas fa-cogs"></i> Check Jobs Status
                        </a>
                        <a href="admin.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?> 