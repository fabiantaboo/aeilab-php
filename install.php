<?php
/**
 * AEI Lab Internal Tool - Installation Script
 * Automatisches Setup für die Konfiguration
 */

$configFile = 'config/config.php';
$configExample = 'config/config.example.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - AEI Lab Internal Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-brain"></i> AEI Lab Internal Tool - Installation</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if (file_exists($configFile)) {
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check-circle"></i> <strong>Konfiguration bereits vorhanden!</strong><br>';
                            echo 'Die config.php existiert bereits. Sie können direkt mit dem <a href="setup.php">Setup</a> fortfahren.';
                            echo '</div>';
                        } elseif (isset($_POST['create_config'])) {
                            if (file_exists($configExample)) {
                                if (copy($configExample, $configFile)) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle"></i> <strong>Konfiguration erfolgreich erstellt!</strong><br>';
                                    echo 'Die config.php wurde aus der Vorlage erstellt. Bitte bearbeiten Sie sie jetzt.';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-danger">';
                                    echo '<i class="fas fa-exclamation-triangle"></i> <strong>Fehler!</strong><br>';
                                    echo 'Die Konfigurationsdatei konnte nicht erstellt werden. Überprüfen Sie die Dateiberechtigungen.';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="fas fa-exclamation-triangle"></i> <strong>Fehler!</strong><br>';
                                echo 'Die Vorlage config.example.php wurde nicht gefunden.';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">';
                            echo '<i class="fas fa-info-circle"></i> <strong>Willkommen!</strong><br>';
                            echo 'Dieses Tool hilft Ihnen bei der ersten Einrichtung des AEI Lab Internal Tools.';
                            echo '</div>';
                        }
                        ?>

                        <h5>Installationsschritte:</h5>
                        <ol>
                            <li><strong>Konfiguration erstellen</strong>
                                <?php if (file_exists($configFile)): ?>
                                    <span class="badge bg-success ms-2">✓ Erledigt</span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-2">Ausstehend</span>
                                <?php endif; ?>
                            </li>
                            <li><strong>Datenbankdaten eintragen</strong>
                                <?php if (file_exists($configFile)): ?>
                                    <span class="badge bg-info ms-2">Manuell bearbeiten</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Warten</span>
                                <?php endif; ?>
                            </li>
                            <li><strong>Datenbank-Setup ausführen</strong>
                                <span class="badge bg-secondary ms-2">Automatisch</span>
                            </li>
                        </ol>

                        <?php if (!file_exists($configFile)): ?>
                            <div class="mt-4">
                                <h6>Schritt 1: Konfiguration erstellen</h6>
                                <p>Erstellen Sie die Konfigurationsdatei aus der Vorlage:</p>
                                <form method="POST" action="">
                                    <button type="submit" name="create_config" class="btn btn-primary">
                                        <i class="fas fa-copy"></i> config.php erstellen
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="mt-4">
                                <h6>Schritt 2: Konfiguration bearbeiten</h6>
                                <p>Bearbeiten Sie die Datei <code>config/config.php</code> und tragen Sie Ihre Datenbankdaten ein:</p>
                                <pre class="bg-light p-3 rounded"><code>define('DB_HOST', 'localhost');
define('DB_NAME', 'aeilab_internal');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('BASE_URL', 'http://yourdomain.com/aeilab-php/');</code></pre>
                                
                                <h6 class="mt-4">Schritt 3: Datenbank-Setup</h6>
                                <p>Nach der Konfiguration können Sie mit dem Setup fortfahren:</p>
                                <a href="setup.php" class="btn btn-success">
                                    <i class="fas fa-arrow-right"></i> Zum Datenbank-Setup
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h6>Weitere Hilfe:</h6>
                            <ul>
                                <li><a href="INSTALLATION.md" target="_blank">Vollständige Installationsanleitung</a></li>
                                <li><a href="setup.php">Datenbank-Setup und Status</a></li>
                                <li><a href="index.php">Zur Anwendung</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 