<?php
// app/Models/Base/Model.php

namespace App\Models\Base;

use App\Config\Database;
use mysqli;
use mysqli_stmt;
use Exception;

abstract class Model
{
    protected Database $db;
    protected mysqli $conn;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data ?: null;
    }
    
    /**
     * Find all records
     */
    public function findAll(array $conditions = [], array $orderBy = []): array
    {
        $query = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
            }
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        if (!empty($orderBy)) {
            $orderClauses = [];
            foreach ($orderBy as $column => $direction) {
                $orderClauses[] = "$column $direction";
            }
            $query .= " ORDER BY " . implode(", ", $orderClauses);
        }
        
        if (empty($conditions)) {
            $result = $this->conn->query($query);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        }
        
        $stmt = $this->conn->prepare($query);
        
        $types = '';
        $values = [];
        foreach ($conditions as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        return $data;
    }
    
    /**
     * Create new record
     */
    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $query = "INSERT INTO {$this->table} (" . 
                 implode(', ', $columns) . ") VALUES (" . 
                 implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($query);
        
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_null($value)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "$column = ?";
        }
        
        $query = "UPDATE {$this->table} SET " . 
                 implode(', ', $setClauses) . 
                 " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->conn->prepare($query);
        
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $types .= 'i';
        $values[] = $id;
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Execute custom query
     */
    protected function executeQuery(string $query, array $params = []): array
    {
        if (empty($params)) {
            $result = $this->conn->query($query);
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        }
        
        $stmt = $this->conn->prepare($query);
        
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        return $data;
    }
    
    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
            }
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        if (empty($conditions)) {
            $result = $this->conn->query($query);
            $row = $result->fetch_assoc();
            return (int) $row['total'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        $types = '';
        $values = [];
        foreach ($conditions as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int) $row['total'];
    }
}