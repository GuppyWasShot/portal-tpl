<?php
// app/Config/Database.php

namespace App\Config;

use mysqli;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private ?mysqli $connection = null;
    
    // Localhost Configuration
    private string $host = 'localhost';
    private string $user = 'root';
    private string $password = '';
    private string $database = 'db_portal_tpl';
    
    // Neon/Vercel Configuration (commented out for localhost)
    /*
    private string $host;
    private string $user;
    private string $password;
    private string $database;
    
    public function __construct()
    {
        // For Vercel + Neon deployment, use environment variables
        $this->host = getenv('DB_HOST') ?: 'your-neon-host.neon.tech';
        $this->user = getenv('DB_USER') ?: 'your_neon_user';
        $this->password = getenv('DB_PASSWORD') ?: 'your_neon_password';
        $this->database = getenv('DB_NAME') ?: 'db_portal_tpl';
    }
    */
    
    /**
     * Private constructor untuk Singleton pattern
     */
    private function __construct()
    {
        $this->connect();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect(): void
    {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database
            );
            
            if ($this->connection->connect_error) {
                throw new Exception(
                    "Database connection failed: " . $this->connection->connect_error
                );
            }
            
            $this->connection->set_charset('utf8mb4');
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Could not connect to database");
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection(): mysqli
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Close database connection
     */
    public function close(): void
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }
    
    /**
     * Prepare statement
     */
    public function prepare(string $query)
    {
        return $this->connection->prepare($query);
    }
    
    /**
     * Execute query
     */
    public function query(string $query)
    {
        return $this->connection->query($query);
    }
    
    /**
     * Get last insert ID
     */
    public function getInsertId(): int
    {
        return $this->connection->insert_id;
    }
    
    /**
     * Escape string
     */
    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }
}