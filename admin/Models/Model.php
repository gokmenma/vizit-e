<?php

namespace Admin\Models;

use PDO;
use Exception;

class Model {
    public $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $attributes = [];
    protected $isNew = true;

    public function __construct($table = null) {
        if ($table !== null) {
            $this->table = $table;
        } else {
            $this->table = $this->getTableName();
        }

        // 1. First, check if parent's Database helper class exists (highly optimized sharing)
        if (class_exists('\\Core\\Database')) {
            try {
                $this->db = \Core\Database::getInstance()->getConnection();
                return;
            } catch (Exception $e) {
                // If it fails, fall through to the independent connection
            }
        }

        // 2. Portable Fallback: If copied to another project, read from config or setup direct connection
        $configFile = __DIR__ . '/../../config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            // Default sandbox config
            $config = [
                'db_host' => 'localhost',
                'db_name' => 'vizite',
                'db_user' => 'root',
                'db_pass' => ''
            ];
        }

        $host = $config['db_host'] ?? 'localhost';
        $dbname = $config['db_name'] ?? 'vizite';
        $user = $config['db_user'] ?? 'root';
        $pass = $config['db_pass'] ?? '';

        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);
        } catch (Exception $e) {
            die("Database Connection Error in Admin Module: " . $e->getMessage());
        }
    }

    /**
     * Guess table name from model class name if not specified
     */
    protected function getTableName() {
        $className = get_called_class();
        $parts = explode('\\', $className);
        $className = end($parts);
        $name = strtolower(str_replace('Model', '', $className));
        if (substr($name, -1) === 'y') {
            return substr($name, 0, -1) . 'ies';
        }
        return $name . 's';
    }

    /**
     * Get all records from the table
     * @return array
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Find a single record by its primary key
     * @param mixed $id
     * @return object|false
     */
    public function find($id) {
        if (!$id) return false;
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?? false;
    }

    /**
     * Find records matching a specific column value
     * @param string $column
     * @param mixed $value
     * @param string $sorting
     * @return array
     */
    public function where($column, $value, $sorting = "id ASC") {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ? ORDER BY {$sorting}");
        $stmt->execute([$value]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Save a record using an associative array of attributes
     * @param array $data
     * @return mixed Last inserted ID or execute status
     */
    public function saveWithAttr($data) {
        $this->attributes = $data;
        if (isset($data[$this->primaryKey]) && $data[$this->primaryKey] > 0) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Insert record helper
     */
    protected function insert() {
        $columns = implode(', ', array_keys($this->attributes));
        $placeholders = ':' . implode(', :', array_keys($this->attributes));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");

        foreach ($this->attributes as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $this->isNew = false;
        $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
        return $this->attributes[$this->primaryKey];
    }

    /**
     * Update record helper
     */
    protected function update() {
        $setClause = '';
        foreach ($this->attributes as $key => $value) {
            if ($key === $this->primaryKey) continue;
            $setClause .= "$key = :$key, ";
        }
        $setClause = rtrim($setClause, ', ');

        $stmt = $this->db->prepare("UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :{$this->primaryKey}");

        foreach ($this->attributes as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        return $stmt->execute();
    }

    /**
     * Delete a record by primary key
     * @param mixed $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
}
