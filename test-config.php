<?php
// Test-Datei für die Konfiguration
// Diese Datei nach dem Test wieder löschen!

echo "<h2>AEI Lab - Konfigurationstest</h2>";

if (file_exists('config/config.php')) {
    echo "<p style='color: green;'>✓ config.php gefunden</p>";
    
    try {
        require_once 'config/config.php';
        echo "<p style='color: green;'>✓ config.php erfolgreich geladen</p>";
        
        echo "<h3>Konfiguration:</h3>";
        echo "<ul>";
        echo "<li>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'nicht definiert') . "</li>";
        echo "<li>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'nicht definiert') . "</li>";
        echo "<li>DB_USER: " . (defined('DB_USER') ? DB_USER : 'nicht definiert') . "</li>";
        echo "<li>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'nicht definiert') . "</li>";
        echo "</ul>";
        
        // Test Datenbankverbindung
        echo "<h3>Datenbankverbindung:</h3>";
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            echo "<p style='color: green;'>✓ Datenbankverbindung erfolgreich</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Datenbankverbindung fehlgeschlagen: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Fehler beim Laden der config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ config.php nicht gefunden</p>";
    echo "<p>Bitte erstellen Sie die config.php aus der Vorlage config.example.php</p>";
}

echo "<hr>";
echo "<a href='install.php'>Zur Installation</a> | ";
echo "<a href='setup.php'>Zum Setup</a> | ";
echo "<a href='index.php'>Zur Anwendung</a>";
?> 