<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private $lastError;
    
    private function __construct() {
        $this->connection = null;
        $this->lastError = null;
        
        try {
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }

    public function getLastError() {
        return $this->lastError;
    }
    
    public function prepare($sql) {
        if ($this->connection === null) {
            throw new \RuntimeException('Database connection is not available: ' . $this->lastError);
        }
        return $this->connection->prepare($sql);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
}