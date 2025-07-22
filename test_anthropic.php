<?php
/**
 * Test Script für Anthropic API Connection
 * Führe dieses Script aus, um die API-Verbindung zu testen
 */

// Include bootstrap
require_once 'includes/bootstrap.php';

// Test API connection
try {
    echo "=== Anthropic API Connection Test ===\n\n";
    
    // Check if API key is configured
    if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === 'your_anthropic_api_key_here') {
        echo "❌ FEHLER: Anthropic API Key nicht konfiguriert\n";
        echo "Bitte setze ANTHROPIC_API_KEY in config/config.php\n";
        exit(1);
    }
    
    echo "✅ API Key ist konfiguriert\n";
    
    // Test cURL availability
    if (!function_exists('curl_init')) {
        echo "❌ FEHLER: cURL extension nicht verfügbar\n";
        exit(1);
    }
    
    echo "✅ cURL extension ist verfügbar\n";
    
    // Initialize API
    $anthropicAPI = new AnthropicAPI();
    echo "✅ AnthropicAPI Klasse initialisiert\n";
    
    // Test API connection
    echo "\n--- Testing API Connection ---\n";
    
    $response = $anthropicAPI->generateResponse(
        "You are a helpful assistant. Please respond briefly.",
        [
            ['role' => 'user', 'content' => 'Hello, this is a test. Please respond with "Test successful!"']
        ],
        50
    );
    
    if ($response['success']) {
        echo "✅ API Connection erfolgreich!\n";
        echo "Response: " . $response['message'] . "\n";
        
        if (isset($response['usage'])) {
            echo "Token Usage: " . json_encode($response['usage']) . "\n";
        }
    } else {
        echo "❌ API Connection fehlgeschlagen: " . $response['error'] . "\n";
        exit(1);
    }
    
    // Test dialog turn generation
    echo "\n--- Testing Dialog Turn Generation ---\n";
    
    $dialogResponse = $anthropicAPI->generateDialogTurn(
        "You are a helpful customer service representative. Be polite and professional.",
        "Customer support for a software product",
        [],
        "AEI",
        "Support Agent",
        "Customer",
        "User"
    );
    
    if ($dialogResponse['success']) {
        echo "✅ Dialog Turn Generation erfolgreich!\n";
        echo "Generated Message: " . $dialogResponse['message'] . "\n";
    } else {
        echo "❌ Dialog Turn Generation fehlgeschlagen: " . $dialogResponse['error'] . "\n";
        exit(1);
    }
    
    // Test database connection
    echo "\n--- Testing Database Connection ---\n";
    
    try {
        $testStats = $dialogJob->getStats();
        echo "✅ Database Connection erfolgreich!\n";
        echo "Current Job Stats: " . json_encode($testStats) . "\n";
    } catch (Exception $e) {
        echo "❌ Database Connection fehlgeschlagen: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "\n=== Alle Tests erfolgreich! ===\n";
    echo "Das System ist bereit für Background Processing.\n";
    echo "Nächste Schritte:\n";
    echo "1. Cron-Job einrichten (siehe CRON_SETUP.md)\n";
    echo "2. Einen Dialog erstellen\n";
    echo "3. Dialog-Status überwachen (dialogs.php)\n";
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}
?> 