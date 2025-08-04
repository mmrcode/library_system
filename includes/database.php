<?php
/**
 * Database Connection and Helper Functions
 * Library Management System
 * 
 * @author Mohammad Muqsit Raja
 * @reg_no BCA22739
 * @university University of Mysore
 * @year 2025
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

class Database {
    private $connection;
    private $isClosed = false;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to UTF-8
            $this->connection->set_charset("utf8");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function closeConnection() {
        if ($this->connection && is_object($this->connection) && !$this->isClosed) {
            try {
                $this->connection->close();
                $this->isClosed = true;
            } catch (Exception $e) {
                error_log("Database connection close error: " . $e->getMessage());
            }
        }
    }
    
    public function query($sql, $params = []) {
        try {
            if (empty($params)) {
                $result = $this->connection->query($sql);
                if ($result === false) {
                    throw new Exception("Query failed: " . $this->connection->error);
                }
                return $result;
            } else {
                $stmt = $this->connection->prepare($sql);
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $this->connection->error);
                }
                
                if (!empty($params)) {
                    $types = $this->getParamTypes($params);
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
                
                return $result;
            }
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = str_repeat('?,', count($data) - 1) . '?';
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            $values = array_values($data);
            $types = $this->getParamTypes($values);
            $stmt->bind_param($types, ...$values);
            
            $result = $stmt->execute();
            $insertId = $this->connection->insert_id;
            $stmt->close();
            
            if ($result) {
                return $insertId;
            } else {
                throw new Exception("Insert failed: " . $this->connection->error);
            }
        } catch (Exception $e) {
            error_log("Database insert error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            $values = array_merge(array_values($data), $whereParams);
            $types = $this->getParamTypes($values);
            $stmt->bind_param($types, ...$values);
            
            $result = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
        } catch (Exception $e) {
            error_log("Database update error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete($table, $where, $whereParams = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($whereParams)) {
                $types = $this->getParamTypes($whereParams);
                $stmt->bind_param($types, ...$whereParams);
            }
            
            $result = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
        } catch (Exception $e) {
            error_log("Database delete error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetch_assoc();
    }
    
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function fetchColumn($sql, $params = []) {
        $result = $this->query($sql, $params);
        $row = $result->fetch_row();
        return $row ? $row[0] : null;
    }
    
    /**
     * Fetch the first row of a result set (alias of fetchOne for clarity)
     */
    public function fetchRow($sql, $params = []) {
        return $this->fetchOne($sql, $params);
    }
    
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }
    
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob
            }
        }
        return $types;
    }
    
    public function __destruct() {
        if ($this->connection && is_object($this->connection) && !$this->isClosed) {
            try {
                $this->connection->close();
                $this->isClosed = true;
            } catch (Exception $e) {
                // Connection might already be closed, ignore the error
                error_log("Database connection close error: " . $e->getMessage());
            }
        }
    }
}

// Helper functions for common database operations
function getDB() {
    return Database::getInstance();
}

function executeQuery($sql, $params = []) {
    return getDB()->query($sql, $params);
}

function fetchOne($sql, $params = []) {
    return getDB()->fetchOne($sql, $params);
}

function fetchAll($sql, $params = []) {
    return getDB()->fetchAll($sql, $params);
}

function insertRecord($table, $data) {
    return getDB()->insert($table, $data);
}

function updateRecord($table, $data, $where, $whereParams = []) {
    return getDB()->update($table, $data, $where, $whereParams);
}

function deleteRecord($table, $where, $whereParams = []) {
    return getDB()->delete($table, $where, $whereParams);
}

?>
