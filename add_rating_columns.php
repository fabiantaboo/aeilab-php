<?php
require_once 'includes/bootstrap.php';

try {
    // Add rating columns to dialog_messages table
    $db->query("ALTER TABLE dialog_messages ADD COLUMN IF NOT EXISTS rating_thumbs_up INT DEFAULT 0");
    $db->query("ALTER TABLE dialog_messages ADD COLUMN IF NOT EXISTS rating_thumbs_down INT DEFAULT 0");
    
    echo "Rating columns added successfully!<br>";
    
    // Test if columns exist
    $result = $db->query("SHOW COLUMNS FROM dialog_messages LIKE 'rating_%'");
    $columns = $result->fetchAll();
    
    echo "Found rating columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>