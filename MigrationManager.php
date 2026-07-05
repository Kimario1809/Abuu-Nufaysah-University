<?php
namespace App\Database\Migrations;

use App\Core\Database;

class MigrationManager {
    private $db;
    private $migrationsTable = 'migrations';
    private $migrationPath;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->migrationPath = __DIR__;
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations table if it doesn't exist
     */
    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }
    
    /**
     * Run all pending migrations
     */
    public function migrate() {
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            echo "Nothing to migrate.\n";
            return;
        }
        
        $batch = $this->getNextBatchNumber();
        
        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration, $batch);
        }
        
        echo "Migration completed successfully.\n";
    }
    
    /**
     * Rollback the last batch of migrations
     */
    public function rollback() {
        $lastBatch = $this->getLastBatchNumber();
        
        if ($lastBatch === null) {
            echo "Nothing to rollback.\n";
            return;
        }
        
        $migrations = $this->getMigrationsByBatch($lastBatch);
        
        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
        }
        
        echo "Rollback completed successfully.\n";
    }
    
    /**
     * Reset all migrations
     */
    public function reset() {
        $migrations = $this->getAllMigrations();
        
        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
        }
        
        echo "Reset completed successfully.\n";
    }
    
    /**
     * Refresh migrations (reset + migrate)
     */
    public function refresh() {
        $this->reset();
        $this->migrate();
        echo "Refresh completed successfully.\n";
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations() {
        $files = glob($this->migrationPath . '/*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (!$this->isMigrationExecuted($filename)) {
                $migrations[] = $filename;
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Check if migration has been executed
     */
    private function isMigrationExecuted($migration) {
        $sql = "SELECT * FROM `{$this->migrationsTable}` WHERE migration = ?";
        $stmt = $this->db->query($sql, [$migration]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatchNumber() {
        $sql = "SELECT MAX(batch) as max_batch FROM `{$this->migrationsTable}`";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    /**
     * Get last batch number
     */
    private function getLastBatchNumber() {
        $sql = "SELECT MAX(batch) as max_batch FROM `{$this->migrationsTable}`";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['max_batch'] ?? null;
    }
    
    /**
     * Get migrations by batch
     */
    private function getMigrationsByBatch($batch) {
        $sql = "SELECT * FROM `{$this->migrationsTable}` WHERE batch = ? ORDER BY id DESC";
        $stmt = $this->db->query($sql, [$batch]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all migrations
     */
    private function getAllMigrations() {
        $sql = "SELECT * FROM `{$this->migrationsTable}` ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Run a specific migration
     */
    private function runMigration($migration, $batch) {
        echo "Running migration: $migration\n";
        
        require_once $this->migrationPath . '/' . $migration . '.php';
        
        $className = $this->getMigrationClassName($migration);
        $instance = new $className();
        $instance->up();
        
        // Record migration
        $sql = "INSERT INTO `{$this->migrationsTable}` (migration, batch) VALUES (?, ?)";
        $this->db->query($sql, [$migration, $batch]);
    }
    
    /**
     * Rollback a specific migration
     */
    private function rollbackMigration($migration) {
        echo "Rolling back: {$migration['migration']}\n";
        
        require_once $this->migrationPath . '/' . $migration['migration'] . '.php';
        
        $className = $this->getMigrationClassName($migration['migration']);
        $instance = new $className();
        $instance->down();
        
        // Delete record
        $sql = "DELETE FROM `{$this->migrationsTable}` WHERE id = ?";
        $this->db->query($sql, [$migration['id']]);
    }
    
    /**
     * Get migration class name from filename
     */
    private function getMigrationClassName($filename) {
        // Remove timestamp prefix (e.g., 001_create_users_table -> CreateUsersTable)
        $parts = explode('_', $filename, 2);
        if (count($parts) > 1) {
            $name = $parts[1];
        } else {
            $name = $filename;
        }
        
        return 'App\\Database\\Migrations\\' . $this->convertToPascalCase($name);
    }
    
    /**
     * Convert snake_case to PascalCase
     */
    private function convertToPascalCase($string) {
        $parts = explode('_', $string);
        $parts = array_map('ucfirst', $parts);
        return implode('', $parts);
    }
}