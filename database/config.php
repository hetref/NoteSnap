<?php

define('DB_HOST', 'notesnap-shindearyan179-ece4.h.aivencloud.com');
define('DB_USER', 'avnadmin');     // Replace with your MySQL username
define('DB_PASS', 'AVNS_W7waXklMvyrxC4crN5x');         // Replace with your MySQL password
define('DB_NAME', 'defaultdb'); // Database name

class Database
{
    private $connection;
    private static $instance;

    private function __construct()
    {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function query($sql)
    {
        return $this->connection->query($sql);
    }

    public function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }

    public function escapeString($string)
    {
        return $this->connection->real_escape_string($string);
    }
}
