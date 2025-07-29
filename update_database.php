<?php
require_once 'includes/bootstrap.php';
requireAuth(); // Require authentication

echo "<h2>Database Update für Rating System</h2>";
echo "<p>Füge Rating-Spalten zur dialog_messages Tabelle hinzu...</p>";

try {
    // Try to add the columns
    $sql1 = "ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_up INT DEFAULT 0";
    $sql2 = "ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_down INT DEFAULT 0";
    
    try {
        $db->query($sql1);
        echo "<p style='color: green;'>✓ rating_thumbs_up Spalte hinzugefügt</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ rating_thumbs_up Spalte existiert bereits</p>";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->query($sql2);
        echo "<p style='color: green;'>✓ rating_thumbs_down Spalte hinzugefügt</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ rating_thumbs_down Spalte existiert bereits</p>";
        } else {
            throw $e;
        }
    }
    
    // Verify columns exist
    $result = $db->query("SHOW COLUMNS FROM dialog_messages LIKE 'rating_%'");
    $columns = $result->fetchAll();
    
    echo "<h3>Gefundene Rating-Spalten:</h3>";
    if (count($columns) > 0) {
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
        }
        echo "</ul>";
        echo "<p style='color: green; font-weight: bold;'>✓ Rating System ist bereit!</p>";
        echo "<p><a href='dialogs.php'>Zurück zu den Dialogen</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Keine Rating-Spalten gefunden</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>