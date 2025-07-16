# JSON Download Feature für Anthropic Requests

## Übersicht
Das Dialog-System speichert jetzt die kompletten Anthropic API-Requests für jeden generierten Turn und bietet Download-Funktionen für Debugging und Analyse.

## Features

### 1. Automatische Request-Speicherung
- Jeder Background-generierte Turn speichert den kompletten Anthropic-Request als JSON
- Inklusive System-Prompts, Conversation-History, und API-Parameter
- Gespeichert in der `dialog_messages` Tabelle in der Spalte `anthropic_request_json`

### 2. Einzelne JSON-Downloads
- **Zugriff:** Dialog-View-Seite → Jeder Turn hat einen "JSON"-Button
- **Format:** `anthropic_request_dialog_[ID]_turn_[N]_[timestamp].json`
- **Inhalt:** 
  - Metadata (Dialog-Info, Character-Info, Timestamps)
  - Kompletter Anthropic-Request mit allen Parametern
  - Anthropic-Response mit Usage-Statistiken

### 3. Bulk-Download (ZIP)
- **Zugriff:** Dialog-View-Seite → "Download All JSON"-Button
- **Format:** `anthropic_requests_dialog_[ID]_[timestamp].zip`
- **Inhalt:** 
  - Alle JSON-Files des Dialogs
  - Sortiert nach Turn-Nummern
  - Dateinamen: `turn_[NN]_[TYPE]_[CHARACTER].json`

### 4. Berechtigungen
- Nur für Dialog-Ersteller und Admins
- Sichere Download-Route mit Authentifizierung
- Keine öffentlichen Links

## JSON-Struktur

### Einzelne Request-Datei
```json
{
    "metadata": {
        "downloaded_at": "2024-01-15 14:30:00",
        "downloaded_by": "username",
        "dialog_id": 123,
        "dialog_name": "Customer Support Dialog",
        "message_id": 456,
        "turn_number": 3,
        "character_name": "Support Agent",
        "character_type": "AEI",
        "message_created_at": "2024-01-15 14:25:00"
    },
    "request_data": {
        "dialog_id": 123,
        "character_id": 5,
        "character_name": "Support Agent",
        "character_type": "AEI",
        "system_prompt": "You are a helpful customer service...",
        "topic": "Software bug report",
        "conversation_history": [
            {
                "character": "Customer",
                "type": "User",
                "message": "I'm having trouble with...",
                "turn": 1
            },
            {
                "character": "Support Agent",
                "type": "AEI",
                "message": "I understand your issue...",
                "turn": 2
            }
        ],
        "turn_number": 3,
        "anthropic_request": {
            "model": "claude-3-sonnet-20240229",
            "system": "Combined system prompt with context",
            "messages": [
                {
                    "role": "user",
                    "content": "Previous conversation messages"
                },
                {
                    "role": "assistant", 
                    "content": "Previous responses"
                }
            ],
            "max_tokens": 1000
        },
        "anthropic_response": {
            "success": true,
            "message": "Generated response text",
            "usage": {
                "input_tokens": 150,
                "output_tokens": 87
            },
            "anthropic_messages": [...]
        },
        "timestamp": "2024-01-15 14:25:00"
    }
}
```

## Verwendung

### Debugging
1. **API-Fehler analysieren:** Schaue dir die exakten Parameter an, die an Anthropic gesendet wurden
2. **Prompt-Optimierung:** Analysiere System-Prompts und deren Wirkung
3. **Conversation-Flow:** Verfolge die komplette Gesprächsentwicklung

### Datenanalyse
1. **Token-Usage:** Überwache API-Kosten und Effizienz
2. **Response-Qualität:** Analysiere Antworten in Relation zu Prompts
3. **Character-Performance:** Vergleiche verschiedene Character-Konfigurationen

### Reproduktion
1. **Exakte Wiederholung:** Nutze die gespeicherten Requests für A/B-Tests
2. **Prompt-Experimente:** Teste Variationen mit identischen Parametern
3. **Fehleranalyse:** Reproduziere problematische Situationen

## Technische Details

### Datenspeicherung
- **Tabelle:** `dialog_messages`
- **Spalte:** `anthropic_request_json` (TEXT)
- **Format:** JSON-String mit kompletten Request-Daten
- **Größe:** Durchschnittlich 2-10 KB pro Request

### Performance
- Automatische Bereinigung alter Requests nach 30 Tagen
- Komprimierung für große Conversation-Histories
- Asynchrone Speicherung während Background-Processing

### Sicherheit
- Requests enthalten keine API-Keys
- Zugriff nur für berechtigte Benutzer
- Logs für alle Downloads
- Temporäre Dateien werden automatisch gelöscht

## Troubleshooting

### Häufige Probleme

1. **"No Anthropic request data found"**
   - Nur Background-generierte Messages haben Request-Daten
   - Manuell erstellte Messages haben keine API-Daten

2. **ZIP-Download schlägt fehl**
   - Prüfe Schreibrechte im tmp-Verzeichnis
   - Stelle sicher, dass ZipArchive-Extension installiert ist

3. **JSON-Parsing-Fehler**
   - Korrupte Daten in der Datenbank
   - Führe eine Bereinigung der dialog_messages-Tabelle durch

### Wartung
```sql
-- Alte Request-Daten löschen (älter als 30 Tage)
UPDATE dialog_messages 
SET anthropic_request_json = NULL 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Speicherplatz-Analyse
SELECT 
    dialog_id,
    COUNT(*) as message_count,
    SUM(LENGTH(anthropic_request_json))/1024 as total_kb
FROM dialog_messages 
WHERE anthropic_request_json IS NOT NULL 
GROUP BY dialog_id;
```

## Erweiterungen

### Geplante Features
1. **Request-Vergleich:** Side-by-side-Vergleich von Requests
2. **Batch-Analysis:** Analyse mehrerer Dialoge gleichzeitig
3. **Export-Formate:** CSV, Excel-Export für Statistiken
4. **API-Replay:** Wiederholung von Requests für Testing

### Konfiguration
In `config/config.php` können Sie einstellen:
```php
// JSON Request Storage
define('STORE_ANTHROPIC_REQUESTS', true);
define('REQUEST_RETENTION_DAYS', 30);
define('MAX_REQUEST_SIZE_KB', 50);
```

## Integration

### Mit bestehenden Tools
- **Logging:** Requests werden in PHP-Logs protokolliert
- **Monitoring:** Job-Status-Seite zeigt Request-Statistiken
- **Backup:** JSON-Daten werden in DB-Backups eingeschlossen

### APIs
Die gespeicherten Requests können über die bestehende Datenbank-API abgerufen werden:
```php
$dialog = new Dialog($database);
$messages = $dialog->getMessages($dialogId);
foreach ($messages as $message) {
    if ($message['anthropic_request_json']) {
        $requestData = json_decode($message['anthropic_request_json'], true);
        // Process request data
    }
}
``` 