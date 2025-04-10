<?php

require_once 'database_config.php';

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST .
            ";port=" . DB_PORT .
            ";dbname=" . DB_NAME .
            ";ssl-mode=" . DB_SSL_MODE;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Initialize tables immediately after connection
            $this->initializeTables();
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    private function initializeTables()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_USERS . " (
                uuid VARCHAR(36) PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                hashed_password VARCHAR(255) NOT NULL,
                security_question TEXT NOT NULL,
                security_answer TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username)
            )";

            $this->connection->exec($sql);

            $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_NOTES . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_uuid VARCHAR(36) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                tags TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_uuid (user_uuid),
                FOREIGN KEY (user_uuid) REFERENCES " . TABLE_USERS . "(uuid) ON DELETE CASCADE
            )";

            $this->connection->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Table creation failed: " . $e->getMessage());
        }
    }

    public function ensureTablesExist()
    {
        return $this->initializeTables();
    }
}
