<?php
/**
 * Database Connection Class
 * Handles database connection using PDO
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $this->pdo = new PDO($dsn, $this->username, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // If database doesn't exist, try to create it automatically
                if ($e->getCode() == 1049) { // Database doesn't exist
                    require_once __DIR__ . '/Setup.php';
                    $setup = new Setup();
                    if ($setup->initialize()) {
                        // Try again after setup
                        $this->pdo = new PDO($dsn, $this->username, $this->password);
                        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    } else {
                        throw new Exception("Database setup failed. Please check your configuration or run setup.php manually.");
                    }
                } else {
                    throw new Exception("Database connection failed: " . $e->getMessage());
                }
            }
        }
        return $this->pdo;
    }
    
    /**
     * Execute a prepared statement
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Get single row
     * @param string $query
     * @param array $params
     * @return array|false
     */
    public function fetch($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }
    
    /**
     * Get all rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last inserted ID
     * @return string
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
}
?> 