<?php
/**
 * Test script to verify dialog deletion functionality
 * This script can be accessed via browser to test the dialog deletion
 */

require_once 'includes/bootstrap.php';

// Only allow admin access for testing
requireAuth();
if (!$user->isAdmin()) {
    echo "Only admin can run this test";
    exit;
}

echo "<h2>Dialog Deletion Test</h2>";

try {
    // Get all dialogs (should only show active ones)
    $allDialogs = $dialog->getAll();
    echo "<h3>Active Dialogs (" . count($allDialogs) . "):</h3>";
    
    if (!empty($allDialogs)) {
        echo "<ul>";
        foreach ($allDialogs as $dialogItem) {
            echo "<li>ID: {$dialogItem['id']}, Name: {$dialogItem['name']}, Active: " . ($dialogItem['is_active'] ? 'Yes' : 'No') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No active dialogs found.</p>";
    }
    
    // Test getting a specific dialog by ID
    if (!empty($allDialogs)) {
        $testDialogId = $allDialogs[0]['id'];
        echo "<h3>Testing getById() for dialog ID {$testDialogId}:</h3>";
        
        $specificDialog = $dialog->getById($testDialogId);
        if ($specificDialog) {
            echo "<p>✓ Dialog found: {$specificDialog['name']}</p>";
        } else {
            echo "<p>✗ Dialog not found</p>";
        }
    }
    
    echo "<h3>Database Check:</h3>";
    // Check database directly for all dialogs including deleted ones
    $allInDB = $db->fetchAll("SELECT id, name, is_active FROM dialogs ORDER BY created_at DESC");
    echo "<p>Total dialogs in database: " . count($allInDB) . "</p>";
    
    if (!empty($allInDB)) {
        echo "<ul>";
        foreach ($allInDB as $dbDialog) {
            $status = $dbDialog['is_active'] ? 'Active' : 'Deleted';
            echo "<li>ID: {$dbDialog['id']}, Name: {$dbDialog['name']}, Status: {$status}</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Test completed successfully!</strong></p>";
    echo "<p><a href='dialogs.php'>← Back to Dialogs</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>