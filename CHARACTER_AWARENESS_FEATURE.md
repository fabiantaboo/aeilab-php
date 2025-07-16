# Character Awareness Feature

## Übersicht
Das Dialog-System wurde erweitert, sodass Charaktere jetzt wissen, wer sie sind und mit wem sie sprechen. Diese Verbesserung macht die Dialoge natürlicher und persönlicher.

## Neue Features

### 1. **Character Identity Awareness**
- Jeder Character weiß seinen eigenen Namen und Typ (AEI oder User)
- Wird automatisch in das System-Prompt eingebaut
- Beispiel: `"You are Lisa (AEI character)."`

### 2. **Chat Partner Awareness**
- Jeder Character weiß, mit wem er spricht
- Inklusive Name und Typ des Chat-Partners
- Beispiel: `"You are talking with Max (User character)."`

### 3. **Intelligente System-Prompt-Generierung**
- Dynamische Erstellung des System-Prompts basierend auf verfügbaren Informationen
- Fallback-Mechanismus wenn Namen nicht verfügbar sind
- Konsistente Formatierung

## Vorher vs. Nachher

### **Vorher (Hardcodiert):**
```
You are participating in a dialog about: love
Character type: AEI
Respond naturally and stay in character...
```

### **Nachher (Intelligent):**
```
You are participating in a dialog about: love
You are Lisa (AEI character).
You are talking with Max (User character).
Respond naturally and stay in character...
```

## Technische Implementation

### API-Methode erweitert:
```php
public function generateDialogTurn(
    $characterSystemPrompt, 
    $topic, 
    $conversationHistory, 
    $characterType, 
    $characterName = null,      // NEU
    $partnerName = null,        // NEU
    $partnerType = null         // NEU
)
```

### Background-Processor Integration:
```php
// Automatische Ermittlung des Chat-Partners
$partnerCharacterType = ($nextCharacterType === 'AEI') ? 'User' : 'AEI';
$partnerCharacterId = ($nextCharacterType === 'AEI') ? 
    $dialogData['user_character_id'] : 
    $dialogData['aei_character_id'];

// API-Aufruf mit vollständigen Informationen
$response = $anthropicAPI->generateDialogTurn(
    $characterData['system_prompt'],
    $dialogData['topic'],
    $formattedHistory,
    $nextCharacterType,
    $characterData['name'],                    // Eigener Name
    $partnerCharacterData['name'],             // Partner Name
    $partnerCharacterType                      // Partner Typ
);
```

## Beispiele

### **Beispiel 1: Erstes Gespräch**
Lisa (AEI) spricht mit Max (User) über "Freundschaft":

**System-Prompt:**
```
You are Lisa, a friendly and empathetic AEI assistant. You value deep connections.

You are participating in a dialog about: Freundschaft
You are Lisa (AEI character).
You are talking with Max (User character).
Respond naturally and stay in character. Keep responses conversational and engaging.
This is part of a training dialog, so make it realistic and helpful.
```

**Mögliche Antwort:**
> "Hey Max! Freundschaft ist für mich so wichtig. Ich finde es schön, dass wir darüber sprechen können. Was bedeutet Freundschaft für dich?"

### **Beispiel 2: Mit Conversation History**
Fortsetzung des Gesprächs mit vorheriger Historie:

**Message History:**
```
user: "Hi Lisa! Lass uns über Freundschaft reden."
assistant: "Hey Max! Freundschaft ist für mich so wichtig..."
user: "Ich denke, Vertrauen ist das Wichtigste."
```

**Mögliche Antwort:**
> "Absolut, Max! Ohne Vertrauen kann keine echte Freundschaft entstehen. Ich merke, dass du sehr durchdacht über solche Dinge nachdenkst."

## Vorteile

### **1. Natürlichere Gespräche**
- Charaktere verwenden Namen in ihren Antworten
- Persönlichere Interaktionen
- Weniger "robotische" Dialoge

### **2. Bessere Konsistenz**
- Charaktere bleiben in ihrer Rolle
- Klare Abgrenzung zwischen AEI und User-Charakteren
- Konsistente Anrede und Tonalität

### **3. Verbesserte Trainingsqualität**
- Realistischere Dialog-Daten
- Bessere Beispiele für AI-Training
- Authentischere Conversation-Patterns

## JSON-Export-Verbesserungen

Die JSON-Downloads enthalten jetzt zusätzliche Informationen:

```json
{
  "request_data": {
    "character_name": "Lisa",
    "character_type": "AEI",
    "partner_character_name": "Max",      // NEU
    "partner_character_type": "User",     // NEU
    "anthropic_request": {
      "system": "You are Lisa (AEI character). You are talking with Max (User character)...",
      "messages": [...]
    }
  }
}
```

## Testing

### Automatisierte Tests:
- `test_character_awareness.php` - Testet die neue Funktionalität
- `test_anthropic.php` - Aktualisiert für neue Parameter

### Test-Szenarien:
1. **AEI Character (Lisa) → User (Max)**
2. **User Character (Max) → AEI (Lisa)**
3. **Conversation mit History**
4. **System-Prompt-Analyse**

## Backward Compatibility

### **Vollständig rückwärtskompatibel:**
- Alte API-Aufrufe funktionieren weiterhin
- Neue Parameter sind optional
- Graceful Fallback wenn Namen nicht verfügbar sind

### **Fallback-Verhalten:**
```php
// Wenn Namen nicht verfügbar sind:
"You are a AEI character."
// Statt:
"You are Lisa (AEI character)."
```

## Konfiguration

### **Keine zusätzliche Konfiguration erforderlich!**
- Feature ist automatisch aktiv
- Nutzt bestehende Character-Daten
- Keine Breaking Changes

### **Empfohlene Character-Konfiguration:**
```php
// In der Character-Erstellung:
$characterData = [
    'name' => 'Lisa',           // Wichtig für Personalisierung
    'type' => 'AEI',           // Bestimmt Verhalten
    'system_prompt' => '...',   // Basis-Persönlichkeit
];
```

## Monitoring

### **Logs überwachen:**
```bash
# Prüfe Character-Awareness in Logs
grep "Character awareness" /var/log/php_errors.log

# Prüfe API-Requests
grep "generateDialogTurn" /var/log/php_errors.log
```

### **JSON-Downloads nutzen:**
- System-Prompts analysieren
- Character-Interaktionen überprüfen
- Qualität der Namensverwendung bewerten

## Zukunft

### **Geplante Erweiterungen:**
1. **Relationship Memory:** Charaktere erinnern sich an vorherige Gespräche
2. **Personality Adaptation:** Charaktere passen sich an den Chat-Partner an
3. **Context Awareness:** Besseres Verständnis für Gesprächskontext
4. **Emotion Recognition:** Erkennung und Reaktion auf Emotionen

### **Potentielle Verbesserungen:**
- Dynamische Personality-Anpassung
- Multi-Character-Dialoge
- Langzeit-Memory-System
- Emotionale Intelligenz

Die Character-Awareness macht das Dialog-System menschlicher und natürlicher! 🎉 