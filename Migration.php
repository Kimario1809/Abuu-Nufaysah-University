<?php
namespace App\Database\Migrations;

use App\Core\Database;

abstract class Migration {
    protected $db;
    protected $table;
    protected $connection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    /**
     * Run the migration
     */
    abstract public function up();
    
    /**
     * Reverse the migration
     */
    abstract public function down();
    
    /**
     * Create table with basic structure
     */
    protected function createTable($table, $callback) {
        $schema = new Schema($this->connection, $table);
        $callback($schema);
        $schema->create();
    }
    
    /**
     * Drop table if exists
     */
    protected function dropTable($table) {
        $this->connection->exec("DROP TABLE IF EXISTS `$table`");
    }
    
    /**
     * Check if table exists
     */
    protected function tableExists($table) {
        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Add foreign key constraint
     */
    protected function addForeignKey($table, $column, $references, $onDelete = 'CASCADE', $onUpdate = 'CASCADE') {
        $sql = "ALTER TABLE `$table` ADD CONSTRAINT `fk_{$table}_{$column}` 
                FOREIGN KEY (`$column`) REFERENCES `$references`(`id`) 
                ON DELETE $onDelete ON UPDATE $onUpdate";
        $this->connection->exec($sql);
    }
    
    /**
     * Drop foreign key constraint
     */
    protected function dropForeignKey($table, $column) {
        $sql = "ALTER TABLE `$table` DROP FOREIGN KEY `fk_{$table}_{$column}`";
        $this->connection->exec($sql);
    }
    
    /**
     * Add index
     */
    protected function addIndex($table, $column, $indexName = null) {
        $indexName = $indexName ?? "idx_{$table}_{$column}";
        $sql = "ALTER TABLE `$table` ADD INDEX `$indexName` (`$column`)";
        $this->connection->exec($sql);
    }
    
    /**
     * Drop index
     */
    protected function dropIndex($table, $indexName) {
        $sql = "ALTER TABLE `$table` DROP INDEX `$indexName`";
        $this->connection->exec($sql);
    }
}

/**
 * Schema Builder Class
 */
class Schema {
    private $connection;
    private $table;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    private $engine = 'InnoDB';
    private $charset = 'utf8mb4';
    private $collation = 'utf8mb4_unicode_ci';
    
    public function __construct($connection, $table) {
        $this->connection = $connection;
        $this->table = $table;
    }
    
    /**
     * Add an auto-incrementing ID column
     */
    public function id() {
        $this->columns[] = "`id` INT PRIMARY KEY AUTO_INCREMENT";
        return $this;
    }
    
    /**
     * Add a string column
     */
    public function string($column, $length = 255, $nullable = false, $default = null) {
        $sql = "`$column` VARCHAR($length)";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        if ($default !== null) $sql .= " DEFAULT '$default'";
        $this->columns[] = $sql;
        return $this;
    }

    public function primary($column = null) {
        if ($column !== null) {
            $last = end($this->columns);
            if ($last !== false) {
                $index = key($this->columns);
                $this->columns[$index] = str_replace(' NOT NULL', '', $last) . ' PRIMARY KEY';
            }
        }
        return $this;
    }
    
    /**
     * Add a text column
     */
    public function text($column, $nullable = false) {
        $sql = "`$column` TEXT";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add an integer column
     */
    public function integer($column, $nullable = false, $default = null) {
        $sql = "`$column` INT";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        if ($default !== null) $sql .= " DEFAULT $default";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a decimal column
     */
    public function decimal($column, $precision = 10, $scale = 2, $nullable = false, $default = null) {
        $sql = "`$column` DECIMAL($precision, $scale)";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        if ($default !== null) $sql .= " DEFAULT $default";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a boolean column
     */
    public function boolean($column, $default = null) {
        $sql = "`$column` BOOLEAN";
        if ($default !== null) $sql .= " DEFAULT " . ($default ? '1' : '0');
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a date column
     */
    public function date($column, $nullable = true) {
        $sql = "`$column` DATE";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        $this->columns[] = $sql;
        return $this;
    }

    /**
     * Add a time column
     */
    public function time($column, $nullable = true) {
        $sql = "`$column` TIME";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a datetime column
     */
    public function datetime($column, $nullable = true) {
        $sql = "`$column` DATETIME";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a timestamp column with default CURRENT_TIMESTAMP
     */
    public function timestamp($column, $nullable = false) {
        $sql = "`$column` TIMESTAMP";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add an enum column
     */
    public function enum($column, $values, $nullable = false, $default = null) {
        $valuesStr = "'" . implode("', '", $values) . "'";
        $sql = "`$column` ENUM($valuesStr)";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        if ($default !== null) $sql .= " DEFAULT '$default'";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add a JSON column
     */
    public function json($column, $nullable = true) {
        $sql = "`$column` JSON";
        if ($nullable) $sql .= " NULL";
        else $sql .= " NOT NULL";
        $this->columns[] = $sql;
        return $this;
    }
    
    /**
     * Add unique constraint
     */
    public function unique($column = null, $secondColumn = null) {
        if ($column === null) {
            return $this;
        }

        if (is_array($column)) {
            $cols = implode('`, `', $column);
            $this->indexes[] = "UNIQUE KEY `unique_{$this->table}_" . implode('_', $column) . "` (`$cols`)";
            return $this;
        }

        if ($secondColumn !== null) {
            $this->indexes[] = "UNIQUE KEY `unique_{$this->table}_{$column}_{$secondColumn}` (`$column`, `$secondColumn`)";
            return $this;
        }

        $this->indexes[] = "UNIQUE KEY `unique_{$this->table}_{$column}` (`$column`)";
        return $this;
    }
    
    /**
     * Add index
     */
    public function index($column) {
        $this->indexes[] = "INDEX `idx_{$this->table}_{$column}` (`$column`)";
        return $this;
    }
    
    /**
     * Add foreign key
     */
    public function foreign($column, $references, $onDelete = 'CASCADE', $onUpdate = 'CASCADE') {
        $this->foreignKeys[] = "CONSTRAINT `fk_{$this->table}_{$column}` 
                               FOREIGN KEY (`$column`) REFERENCES `$references`(`id`) 
                               ON DELETE $onDelete ON UPDATE $onUpdate";
        return $this;
    }
    
    /**
     * Set table engine
     */
    public function engine($engine) {
        $this->engine = $engine;
        return $this;
    }
    
    /**
     * Set table charset
     */
    public function charset($charset) {
        $this->charset = $charset;
        return $this;
    }
    
    /**
     * Create timestamps (created_at, updated_at)
     */
    public function timestamps() {
        $this->columns[] = "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function nullable() {
        return $this;
    }

    public function default($value) {
        $last = end($this->columns);
        if ($last !== false) {
            $index = key($this->columns);
            $this->columns[$index] = rtrim($last, " ") . " DEFAULT " . (is_string($value) ? "'$value'" : $value);
        }
        return $this;
    }
    
    /**
     * Build and execute the create table query
     */
    public function create() {
        $columns = implode(",\n    ", $this->columns);
        $indexes = !empty($this->indexes) ? ",\n    " . implode(",\n    ", $this->indexes) : "";
        $foreignKeys = !empty($this->foreignKeys) ? ",\n    " . implode(",\n    ", $this->foreignKeys) : "";
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            $columns
            $indexes
            $foreignKeys
        ) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
        
        $this->connection->exec($sql);
    }
}