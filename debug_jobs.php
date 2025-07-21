<?php
/**
 * Debug Script für Dialog Jobs
 * Analysiert die aktuelle Job-Situation und testet Retry-Mechanismus
 */

require_once 'includes/bootstrap.php';

echo "=== Dialog Jobs Debug Tool ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Aktuelle Job-Statistiken
    echo "--- Job-Statistiken ---\n";
    $stats = $dialogJob->getStats();
    foreach ($stats as $status => $count) {
        echo "$status: $count\n";
    }
    echo "\n";

    // 2. Alle Jobs anzeigen
    echo "--- Alle aktiven Jobs ---\n";
    $allJobs = $dialogJob->getActiveJobs();
    if (empty($allJobs)) {
        echo "Keine aktiven Jobs gefunden.\n\n";
    } else {
        foreach ($allJobs as $job) {
            echo "Job #{$job['id']}: {$job['dialog_name']} - Status: {$job['status']}\n";
            echo "  Turn: {$job['current_turn']}/{$job['max_turns']}\n";
            echo "  Last processed: " . ($job['last_processed_at'] ?: 'Never') . "\n";
            if ($job['error_message']) {
                echo "  Error: {$job['error_message']}\n";
            }
            echo "\n";
        }
    }

    // 3. Failed Jobs separat anzeigen
    echo "--- Failed Jobs Details ---\n";
    $failedJobs = $database->fetchAll(
        "SELECT * FROM dialog_jobs WHERE status = 'failed' ORDER BY last_processed_at DESC"
    );
    
    if (empty($failedJobs)) {
        echo "Keine failed Jobs gefunden.\n\n";
    } else {
        foreach ($failedJobs as $job) {
            $timeDiff = $job['last_processed_at'] ? 
                round((time() - strtotime($job['last_processed_at'])) / 60, 1) : 
                'N/A';
            
            echo "Job #{$job['id']}: Dialog {$job['dialog_id']}\n";
            echo "  Status: {$job['status']}\n";
            echo "  Last processed: {$job['last_processed_at']} ({$timeDiff} Minuten her)\n";
            echo "  Error: {$job['error_message']}\n";
            echo "  Sollte retried werden: " . ($timeDiff !== 'N/A' && $timeDiff >= 2 ? 'JA' : 'NEIN') . "\n";
            echo "\n";
        }
    }

    // 4. Teste resetFailedJobs manuell
    echo "--- Teste resetFailedJobs() ---\n";
    $retryCount = $dialogJob->resetFailedJobs();
    echo "Anzahl zurückgesetzter Jobs: $retryCount\n\n";

    // 5. Aktualisierte Statistiken nach Reset
    echo "--- Statistiken nach Reset ---\n";
    $newStats = $dialogJob->getStats();
    foreach ($newStats as $status => $count) {
        echo "$status: $count\n";
    }
    echo "\n";

    // 6. Teste ob Cron-Job läuft
    echo "--- Cron-Job Status ---\n";
    echo "Um zu testen ob der Cron-Job läuft:\n";
    echo "1. Führe aus: grep 'Dialog Processor' /var/log/php* 2>/dev/null | tail -10\n";
    echo "2. Oder manuell testen: php background/dialog_processor.php\n\n";

    // 7. Manueller Background Processing Test
    echo "--- Teste Background Processing manuell ---\n";
    $pendingJobs = $dialogJob->getPendingJobs();
    echo "Pending Jobs gefunden: " . count($pendingJobs) . "\n";
    
    if (!empty($pendingJobs)) {
        echo "Jobs die verarbeitet werden würden:\n";
        foreach ($pendingJobs as $job) {
            echo "- Job #{$job['id']}: {$job['dialog_name']} (Turn {$job['current_turn']}/{$job['max_turns']})\n";
        }
        echo "\nFühre 'php background/dialog_processor.php' aus um diese zu verarbeiten.\n";
    }

} catch (Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Tool beendet ===\n";
?> 