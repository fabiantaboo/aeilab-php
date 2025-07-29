<?php
/**
 * Setup Class for automatic database initialization
 * Creates database and tables if they don't exist
 */
class Setup {
    private $host;
    private $username;
    private $password;
    private $charset;
    private $db_name;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        $this->db_name = DB_NAME;
    }
    
    /**
     * Initialize database and tables
     * @return bool
     */
    public function initialize() {
        try {
            // First, connect without database to create it
            $this->createDatabase();
            
            // Then create tables (this will create all tables if they don't exist)
            $this->createTables();
            
            // Insert default admin user if not exists
            $this->createDefaultAdmin();
            
            return true;
        } catch (Exception $e) {
            error_log("Setup Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create specific table if it doesn't exist
     * @param string $tableName
     * @return bool
     */
    public function createSpecificTable($tableName) {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            
            if ($stmt->fetchColumn() === false) {
                // Table doesn't exist, create it
                $this->createTables(); // This will create all missing tables
                return true;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Setup Error creating table {$tableName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create database if it doesn't exist
     */
    private function createDatabase() {
        $dsn = "mysql:host={$this->host};charset={$this->charset}";
        $pdo = new PDO($dsn, $this->username, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET {$this->charset} COLLATE {$this->charset}_unicode_ci");
        $pdo->exec("USE `{$this->db_name}`");
    }
    
    /**
     * Create tables if they don't exist
     */
    private function createTables() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $pdo = new PDO($dsn, $this->username, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create users table
        $userTableSQL = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($userTableSQL);
        
        // Create dialogs table
        $dialogsTableSQL = "
            CREATE TABLE IF NOT EXISTS dialogs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                aei_character_id INT NOT NULL,
                user_character_id INT NOT NULL,
                topic VARCHAR(200) NOT NULL,
                turns_per_topic INT DEFAULT 5,
                status ENUM('draft', 'in_progress', 'completed') DEFAULT 'draft',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                aei_joy DECIMAL(3,2) DEFAULT 0.5,
                aei_sadness DECIMAL(3,2) DEFAULT 0.5,
                aei_fear DECIMAL(3,2) DEFAULT 0.5,
                aei_anger DECIMAL(3,2) DEFAULT 0.5,
                aei_surprise DECIMAL(3,2) DEFAULT 0.5,
                aei_disgust DECIMAL(3,2) DEFAULT 0.5,
                aei_trust DECIMAL(3,2) DEFAULT 0.5,
                aei_anticipation DECIMAL(3,2) DEFAULT 0.5,
                aei_shame DECIMAL(3,2) DEFAULT 0.5,
                aei_love DECIMAL(3,2) DEFAULT 0.5,
                aei_contempt DECIMAL(3,2) DEFAULT 0.5,
                aei_loneliness DECIMAL(3,2) DEFAULT 0.5,
                aei_pride DECIMAL(3,2) DEFAULT 0.5,
                aei_envy DECIMAL(3,2) DEFAULT 0.5,
                aei_nostalgia DECIMAL(3,2) DEFAULT 0.5,
                aei_gratitude DECIMAL(3,2) DEFAULT 0.5,
                aei_frustration DECIMAL(3,2) DEFAULT 0.5,
                aei_boredom DECIMAL(3,2) DEFAULT 0.5,
                FOREIGN KEY (aei_character_id) REFERENCES characters(id) ON DELETE CASCADE,
                FOREIGN KEY (user_character_id) REFERENCES characters(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_aei_character (aei_character_id),
                INDEX idx_user_character (user_character_id),
                INDEX idx_created_by (created_by),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($dialogsTableSQL);
        
        // Create dialog messages table for storing conversation
        $dialogMessagesTableSQL = "
            CREATE TABLE IF NOT EXISTS dialog_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                dialog_id INT NOT NULL,
                character_id INT NOT NULL,
                message TEXT NOT NULL,
                turn_number INT NOT NULL,
                anthropic_request_json TEXT NULL,
                aei_joy DECIMAL(3,2) NULL,
                aei_sadness DECIMAL(3,2) NULL,
                aei_fear DECIMAL(3,2) NULL,
                aei_anger DECIMAL(3,2) NULL,
                aei_surprise DECIMAL(3,2) NULL,
                aei_disgust DECIMAL(3,2) NULL,
                aei_trust DECIMAL(3,2) NULL,
                aei_anticipation DECIMAL(3,2) NULL,
                aei_shame DECIMAL(3,2) NULL,
                aei_love DECIMAL(3,2) NULL,
                aei_contempt DECIMAL(3,2) NULL,
                aei_loneliness DECIMAL(3,2) NULL,
                aei_pride DECIMAL(3,2) NULL,
                aei_envy DECIMAL(3,2) NULL,
                aei_nostalgia DECIMAL(3,2) NULL,
                aei_gratitude DECIMAL(3,2) NULL,
                aei_frustration DECIMAL(3,2) NULL,
                aei_boredom DECIMAL(3,2) NULL,
                rating_thumbs_up INT DEFAULT 0,
                rating_thumbs_down INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (dialog_id) REFERENCES dialogs(id) ON DELETE CASCADE,
                FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
                INDEX idx_dialog (dialog_id),
                INDEX idx_character (character_id),
                INDEX idx_turn (turn_number)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($dialogMessagesTableSQL);
        
        // Add rating columns to existing dialog_messages table if they don't exist
        try {
            // Check if rating columns already exist
            $checkRatingUpSQL = "SHOW COLUMNS FROM dialog_messages LIKE 'rating_thumbs_up'";
            $stmt = $pdo->query($checkRatingUpSQL);
            $ratingUpExists = $stmt->rowCount() > 0;
            
            $checkRatingDownSQL = "SHOW COLUMNS FROM dialog_messages LIKE 'rating_thumbs_down'";
            $stmt = $pdo->query($checkRatingDownSQL);
            $ratingDownExists = $stmt->rowCount() > 0;
            
            if (!$ratingUpExists) {
                $addRatingUpSQL = "ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_up INT DEFAULT 0 AFTER aei_boredom";
                $pdo->exec($addRatingUpSQL);
            }
            
            if (!$ratingDownExists) {
                $addRatingDownSQL = "ALTER TABLE dialog_messages ADD COLUMN rating_thumbs_down INT DEFAULT 0 AFTER rating_thumbs_up";
                $pdo->exec($addRatingDownSQL);
            }
        } catch (Exception $e) {
            // Columns might already exist or other error, continue
            error_log("Warning: Could not add rating columns: " . $e->getMessage());
        }
        
        // Add anthropic_request_json column to existing dialog_messages table if it doesn't exist
        try {
            // Check if column already exists
            $checkColumnSQL = "SHOW COLUMNS FROM dialog_messages LIKE 'anthropic_request_json'";
            $stmt = $pdo->query($checkColumnSQL);
            $columnExists = $stmt->rowCount() > 0;
            
            if (!$columnExists) {
                $addColumnSQL = "ALTER TABLE dialog_messages ADD COLUMN anthropic_request_json TEXT NULL AFTER turn_number";
                $pdo->exec($addColumnSQL);
            }
        } catch (Exception $e) {
            // Column might already exist or other error, continue
            error_log("Warning: Could not add anthropic_request_json column: " . $e->getMessage());
        }
        
        // Create dialog jobs table for background processing
        $dialogJobsTableSQL = "
            CREATE TABLE IF NOT EXISTS dialog_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                dialog_id INT NOT NULL,
                status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
                current_turn INT DEFAULT 0,
                max_turns INT NOT NULL,
                next_character_type ENUM('AEI', 'User') NOT NULL,
                last_processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                error_message TEXT NULL,
                retry_count INT DEFAULT 0,
                FOREIGN KEY (dialog_id) REFERENCES dialogs(id) ON DELETE CASCADE,
                INDEX idx_dialog_id (dialog_id),
                INDEX idx_status (status),
                INDEX idx_last_processed (last_processed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($dialogJobsTableSQL);
        
        // Add retry_count column to existing dialog_jobs table if it doesn't exist
        try {
            // Check if column already exists
            $checkRetryColumnSQL = "SHOW COLUMNS FROM dialog_jobs LIKE 'retry_count'";
            $stmt = $pdo->query($checkRetryColumnSQL);
            $retryColumnExists = $stmt->rowCount() > 0;
            
            if (!$retryColumnExists) {
                $addRetryColumnSQL = "ALTER TABLE dialog_jobs ADD COLUMN retry_count INT DEFAULT 0 AFTER error_message";
                $pdo->exec($addRetryColumnSQL);
                error_log("Setup: Added retry_count column to dialog_jobs table");
            }
        } catch (Exception $e) {
            // Column might already exist or other error, continue
            error_log("Warning: Could not add retry_count column: " . $e->getMessage());
        }
        
        // Create activity log table
        $activityLogSQL = "
            CREATE TABLE IF NOT EXISTS activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($activityLogSQL);
        
        // Create characters table
        $charactersTableSQL = "
            CREATE TABLE IF NOT EXISTS characters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                type ENUM('AEI', 'User') NOT NULL,
                system_prompt TEXT NOT NULL,
                description TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_type (type),
                INDEX idx_created_by (created_by),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($charactersTableSQL);
        
        // Create character pairings table for suggested AEI-User combinations
        $characterPairingsTableSQL = "
            CREATE TABLE IF NOT EXISTS character_pairings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aei_character_id INT NOT NULL,
                user_character_id INT NOT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (aei_character_id) REFERENCES characters(id) ON DELETE CASCADE,
                FOREIGN KEY (user_character_id) REFERENCES characters(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_pairing (aei_character_id, user_character_id),
                INDEX idx_aei_character (aei_character_id),
                INDEX idx_user_character (user_character_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($characterPairingsTableSQL);
        
        // Add emotional state columns to existing dialogs table if they don't exist
        $emotionalColumns = [
            'aei_joy', 'aei_sadness', 'aei_fear', 'aei_anger', 'aei_surprise', 'aei_disgust',
            'aei_trust', 'aei_anticipation', 'aei_shame', 'aei_love', 'aei_contempt', 
            'aei_loneliness', 'aei_pride', 'aei_envy', 'aei_nostalgia', 'aei_gratitude',
            'aei_frustration', 'aei_boredom'
        ];
        
        foreach ($emotionalColumns as $column) {
            try {
                $checkColumnSQL = "SHOW COLUMNS FROM dialogs LIKE '$column'";
                $stmt = $pdo->query($checkColumnSQL);
                $columnExists = $stmt->rowCount() > 0;
                
                if (!$columnExists) {
                    $addColumnSQL = "ALTER TABLE dialogs ADD COLUMN $column DECIMAL(3,2) DEFAULT 0.5";
                    $pdo->exec($addColumnSQL);
                }
            } catch (Exception $e) {
                error_log("Warning: Could not add $column column: " . $e->getMessage());
            }
        }
        
        // Add emotional state columns to existing dialog_messages table if they don't exist
        $messageEmotionalColumns = [
            'aei_joy', 'aei_sadness', 'aei_fear', 'aei_anger', 'aei_surprise', 'aei_disgust',
            'aei_trust', 'aei_anticipation', 'aei_shame', 'aei_love', 'aei_contempt', 
            'aei_loneliness', 'aei_pride', 'aei_envy', 'aei_nostalgia', 'aei_gratitude',
            'aei_frustration', 'aei_boredom'
        ];
        
        foreach ($messageEmotionalColumns as $column) {
            try {
                $checkColumnSQL = "SHOW COLUMNS FROM dialog_messages LIKE '$column'";
                $stmt = $pdo->query($checkColumnSQL);
                $columnExists = $stmt->rowCount() > 0;
                
                if (!$columnExists) {
                    $addColumnSQL = "ALTER TABLE dialog_messages ADD COLUMN $column DECIMAL(3,2) NULL";
                    $pdo->exec($addColumnSQL);
                }
            } catch (Exception $e) {
                error_log("Warning: Could not add $column column to dialog_messages: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create default admin user if not exists
     */
    private function createDefaultAdmin() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $pdo = new PDO($dsn, $this->username, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = $stmt->fetchColumn() > 0;
        
        if (!$adminExists) {
            // Create default admin user (password: admin123)
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
                VALUES ('admin', 'admin@aeilab.com', ?, 'Admin', 'User', 'admin')
            ");
            $stmt->execute([$passwordHash]);
        }
    }
    
    /**
     * Check if database and tables exist
     * @return bool
     */
    public function isDatabaseInitialized() {
        try {
            $dsn = "mysql:host={$this->host};charset={$this->charset}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database exists
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$this->db_name]);
            $databaseExists = $stmt->fetchColumn() !== false;
            
            if (!$databaseExists) {
                return false;
            }
            
            // Check if all required tables exist
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $requiredTables = ['users', 'characters', 'dialogs', 'dialog_messages', 'dialog_jobs', 'activity_log', 'character_pairings'];
            
            foreach ($requiredTables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->fetchColumn() === false) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get setup status information
     * @return array
     */
    public function getSetupStatus() {
        $status = [
            'database_exists' => false,
            'tables_exist' => false,
            'admin_exists' => false,
            'ready' => false
        ];
        
        try {
            // Check database
            $dsn = "mysql:host={$this->host};charset={$this->charset}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$this->db_name]);
            $status['database_exists'] = $stmt->fetchColumn() !== false;
            
            if ($status['database_exists']) {
                // Check tables
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $pdo = new PDO($dsn, $this->username, $this->password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
                $stmt->execute();
                $status['tables_exist'] = $stmt->fetchColumn() !== false;
                
                if ($status['tables_exist']) {
                    // Check admin user
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
                    $stmt->execute();
                    $status['admin_exists'] = $stmt->fetchColumn() > 0;
                }
            }
            
            $status['ready'] = $status['database_exists'] && $status['tables_exist'] && $status['admin_exists'];
        } catch (Exception $e) {
            // Keep all status as false
        }
        
        return $status;
    }
}
?> 