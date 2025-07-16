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
            
            // Then create tables
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
        
        // Create datasets table for future use
        $datasetsTableSQL = "
            CREATE TABLE IF NOT EXISTS datasets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_created_by (created_by)
            ) ENGINE=InnoDB DEFAULT CHARSET={$this->charset} COLLATE={$this->charset}_unicode_ci
        ";
        
        $pdo->exec($datasetsTableSQL);
        
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
            
            // Check if users table exists
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            $tableExists = $stmt->fetchColumn() !== false;
            
            return $tableExists;
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