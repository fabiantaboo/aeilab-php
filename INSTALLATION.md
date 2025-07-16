# AEI Lab Internal Tool - Installation Guide

## Systemanforderungen

- **PHP**: 7.4 oder höher
- **MariaDB/MySQL**: 5.7 oder höher
- **Webserver**: Apache oder Nginx
- **PHP Extensions**: PDO, pdo_mysql, mbstring, json

## Installation

### 1. Dateien hochladen
Laden Sie alle Projektdateien in Ihr Webserver-Verzeichnis hoch.

### 2. Datenbankverbindung konfigurieren
Bearbeiten Sie die Datei `config/config.php`:
```php
define('DB_HOST', 'localhost');     // Ihr Datenbank-Host
define('DB_NAME', 'aeilab_internal'); // Datenbankname
define('DB_USER', 'root');          // Datenbank-Benutzer
define('DB_PASS', '');              // Datenbank-Passwort
```

### 3. Automatisches Setup
Das System erstellt automatisch die Datenbank und Tabellen beim ersten Aufruf.

**Alternativ**: Besuchen Sie `setup.php` für manuelle Installation.

### 4. Anmeldung
Nach erfolgreicher Installation können Sie sich mit folgenden Daten anmelden:
- **Username**: `admin`
- **Password**: `admin123`

## Verzeichnisstruktur

```
aeilab-php/
├── classes/           # PHP-Klassen
├── config/            # Konfigurationsdateien
├── database/          # SQL-Schema
├── includes/          # Gemeinsame Includes
├── admin.php          # Admin-Panel
├── dashboard.php      # Hauptdashboard
├── index.php          # Startseite
├── login.php          # Login-Seite
├── logout.php         # Logout-Funktion
├── setup.php          # Setup-Seite
└── .htaccess          # Apache-Konfiguration
```

## Sicherheitshinweise

1. **Passwort ändern**: Ändern Sie das Standard-Admin-Passwort nach der Installation
2. **Datenbankzugriff**: Verwenden Sie einen dedizierten Datenbankbenutzer mit minimalen Rechten
3. **HTTPS**: Aktivieren Sie HTTPS in der Produktion
4. **Backup**: Erstellen Sie regelmäßige Backups der Datenbank

## Troubleshooting

### Datenbankverbindung fehlgeschlagen
- Überprüfen Sie die Datenbankdaten in `config/config.php`
- Stellen Sie sicher, dass der MySQL-Server läuft
- Überprüfen Sie die Benutzerrechte

### Setup schlägt fehl
- Besuchen Sie `setup.php` für detaillierte Fehlermeldungen
- Überprüfen Sie PHP-Error-Logs
- Stellen Sie sicher, dass alle PHP-Extensions installiert sind

### Zugriff verweigert
- Überprüfen Sie Dateiberechtigungen (755 für Ordner, 644 für Dateien)
- Stellen Sie sicher, dass `.htaccess` unterstützt wird

## Support

Bei Problemen:
1. Überprüfen Sie die Setup-Seite: `setup.php`
2. Konsultieren Sie die PHP-Error-Logs
3. Stellen Sie sicher, dass alle Systemanforderungen erfüllt sind

## Entwicklung

Das System ist für interne Nutzung konzipiert und beinhaltet:
- Automatisches Datenbank-Setup
- Benutzerauthentifizierung
- Admin-Panel für Benutzerverwaltung
- Responsive Bootstrap-UI
- Sicherheitsfeatures (CSRF-Protection, Session-Management)

---

**Hinweis**: Dies ist ein internes Entwicklungstool für AEI Lab. Stellen Sie sicher, dass es nicht öffentlich zugänglich ist. 