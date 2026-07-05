<?php
namespace App\Core;

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->fetch();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            if (!empty($conditions)) {
                $where = [];
                $params = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "{$key} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit}";
            }
            
            $stmt = $this->db->query($sql, $params ?? []);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function create($data) {
        try {
            $keys = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($keys), '?');
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $this->db->query($sql, $values);
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function update($id, $data) {
        try {
            $keys = array_keys($data);
            $values = array_values($data);
            $set = [];
            
            foreach ($keys as $key) {
                $set[] = "{$key} = ?";
            }
            
            $values[] = $id;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE {$this->primaryKey} = ?";
            $this->db->query($sql, $values);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $this->db->query($sql, [$id]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}