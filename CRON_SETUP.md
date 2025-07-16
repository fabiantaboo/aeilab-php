# Cron Job Setup für Dialog Background Processing

## Übersicht
Das Dialog-System benötigt einen Cron-Job, der alle 30 Sekunden läuft, um automatisch Dialog-Turns über die Anthropic API zu generieren.

## Voraussetzungen
1. **Anthropic API Key**: Stelle sicher, dass in der `config/config.php` ein gültiger Anthropic API Key konfiguriert ist:
   ```php
   define('ANTHROPIC_API_KEY', 'your_actual_api_key_here');
   ```

2. **PHP CLI**: Der Server muss PHP über die Kommandozeile ausführen können.

3. **cURL**: Die cURL-Erweiterung muss installiert sein.

## Cron-Job Einrichtung

### 1. Cron-Job erstellen
Füge den folgenden Cron-Job zur Crontab hinzu:

```bash
# Alle 30 Sekunden Dialog-Processing ausführen
* * * * * /usr/bin/php /path/to/your/project/background/dialog_processor.php
* * * * * sleep 30; /usr/bin/php /path/to/your/project/background/dialog_processor.php
```

### 2. Crontab bearbeiten
```bash
# Crontab bearbeiten
crontab -e

# Oder für einen bestimmten Benutzer
crontab -u username -e
```

### 3. Verifikation
```bash
# Aktuelle Crontab anzeigen
crontab -l

# Cron-Service Status prüfen
systemctl status cron
```

## Erweiterte Konfiguration

### 1. Logging konfigurieren
Das Script loggt automatisch in die PHP-Logs. Für separates Logging:

```bash
# Mit separatem Log-File
* * * * * /usr/bin/php /path/to/project/background/dialog_processor.php >> /var/log/dialog_processor.log 2>&1
* * * * * sleep 30; /usr/bin/php /path/to/project/background/dialog_processor.php >> /var/log/dialog_processor.log 2>&1
```

### 2. Umgebungsvariablen setzen
Falls nötig, setze Umgebungsvariablen im Cron-Job:

```bash
# Mit Umgebungsvariablen
* * * * * /usr/bin/env PHP_PATH=/usr/bin/php /usr/bin/php /path/to/project/background/dialog_processor.php
```

### 3. Fehlerbehandlung
Für robustere Fehlerbehandlung:

```bash
# Mit Fehlerbehandlung und Lock-File
* * * * * /usr/bin/flock -n /tmp/dialog_processor.lock /usr/bin/php /path/to/project/background/dialog_processor.php
* * * * * sleep 30; /usr/bin/flock -n /tmp/dialog_processor.lock /usr/bin/php /path/to/project/background/dialog_processor.php
```

## Monitoring

### 1. Log-Überwachung
```bash
# Logs in Echtzeit verfolgen
tail -f /var/log/php_errors.log | grep "Dialog Processor"

# Letzte Aktivitäten prüfen
grep "Dialog Processor" /var/log/php_errors.log | tail -20
```

### 2. Job-Status prüfen
- Besuche die Jobs-Seite im Web-Interface: `http://yoursite.com/jobs.php`
- Prüfe die Datenbank-Tabelle `dialog_jobs`

### 3. Manuelle Ausführung (Test)
```bash
# Script manuell ausführen
php /path/to/project/background/dialog_processor.php
```

## Troubleshooting

### Häufige Probleme:

1. **API Key nicht konfiguriert**
   - Fehlermeldung: "Anthropic API key not configured"
   - Lösung: API Key in `config/config.php` setzen

2. **PHP nicht gefunden**
   - Fehlermeldung: "php: command not found"
   - Lösung: Vollständigen Pfad zu PHP verwenden (`/usr/bin/php`)

3. **Datei-Berechtigungen**
   - Fehlermeldung: "Permission denied"
   - Lösung: Ausführungsrechte setzen: `chmod +x background/dialog_processor.php`

4. **Datenbank-Verbindung**
   - Fehlermeldung: "Database connection failed"
   - Lösung: Datenbankeinstellungen in `config/config.php` prüfen

### Debug-Modus
Für detailliertes Debugging:

```bash
# Script mit Debug-Ausgabe
php -d display_errors=1 /path/to/project/background/dialog_processor.php
```

## Performance-Optimierung

### 1. Häufigkeit anpassen
Falls das System überlastet ist, kann die Häufigkeit reduziert werden:

```bash
# Alle 60 Sekunden statt 30
* * * * * /usr/bin/php /path/to/project/background/dialog_processor.php
```

### 2. Mehrere Worker
Für hohe Last mehrere Worker-Prozesse:

```bash
# Worker 1
* * * * * /usr/bin/php /path/to/project/background/dialog_processor.php
# Worker 2 (mit 30s Delay)
* * * * * sleep 30; /usr/bin/php /path/to/project/background/dialog_processor.php
```

### 3. Systemd Service (Alternative)
Für bessere Kontrolle kann ein Systemd-Service erstellt werden:

```ini
# /etc/systemd/system/dialog-processor.service
[Unit]
Description=Dialog Background Processor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php background/dialog_processor.php
Restart=always
RestartSec=30

[Install]
WantedBy=multi-user.target
```

## Sicherheitshinweise

1. **API Key sicher aufbewahren**: Nie in öffentlichen Repositories
2. **Logs rotieren**: Verhindere unbegrenzte Log-Größe
3. **Berechtigungen**: Minimale Berechtigungen für Cron-Benutzer
4. **Monitoring**: Überwache ungewöhnliche API-Nutzung

## Support

Bei Problemen:
1. Prüfe die Logs
2. Teste manuelle Ausführung
3. Verifiziere API-Konfiguration
4. Prüfe Datenbankverbindung 