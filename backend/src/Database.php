<?php
// backend/src/Database.php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Database configuration from Docker environment
        $this->host = 'database'; // Docker service name
        $this->db_name = 'appdb';
        $this->username = 'appuser';
        $this->password = 'apppassword';
        $this->port = '5432';
    }

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name;
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    // Test database connection
    public function testConnection() {
        $connection = $this->getConnection();
        
        if ($connection === null) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to database'
            ];
        }

        try {
            // Simple query to test connection
            $stmt = $connection->query('SELECT version()');
            $version = $stmt->fetch();
            
            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'postgres_version' => $version['version']
            ];
        } catch(PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database query failed: ' . $e->getMessage()
            ];
        }
    }

    // Generic CRUD operations base methods
    
    /**
     * Execute a SELECT query
     */
    public function select($query, $params = []) {
        try {
            $connection = $this->getConnection();
            if (!$connection) return null;
            
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Select error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute an INSERT query
     */
    public function insert($query, $params = []) {
        try {
            $connection = $this->getConnection();
            if (!$connection) return false;
            
            $stmt = $connection->prepare($query);
            $result = $stmt->execute($params);
            
            // Return the last inserted ID for PostgreSQL
            return $result ? $connection->lastInsertId() : false;
        } catch(PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute an UPDATE query
     */
    public function update($query, $params = []) {
        try {
            $connection = $this->getConnection();
            if (!$connection) return false;
            
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a DELETE query
     */
    public function delete($query, $params = []) {
        try {
            $connection = $this->getConnection();
            if (!$connection) return false;
            
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
}
