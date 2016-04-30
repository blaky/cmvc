<?php

namespace CacaoFw\Service;

use CacaoFw\DataAccessObject;
use CacaoFw\GenericDAO;

/**
 * Main data handling class.
 *
 * You should only use this class to handle connections and transactions, all
 * CURD operations should be done through the DataAccessObject or a customized
 * DAO Object.
 *
 * @author Bence
 *        
 */
class DataService {
    private static $daoCache = array();
    private static $ds = false;
    private $host;
    private $database;
    private $username;
    private $password;
    private $debugMode;
    private $queryHistory;
    public $connection;

    public static function init($config) {
        if (! self::$ds) {
            self::$ds = new \CacaoFw\Service\DataService($config);
            return self::$ds;
        }
    
    }

    private function __construct($config) {
        $this->queryHistory = array();
        $this->host = $config["db.host"];
        $this->database = $config["db.database"];
        $this->username = $config["db.username"];
        $this->password = $config["db.password"];
        
        $this->debugMode = ! ! $config["debug"];
        
        $this->establishConnection();
    
    }

    private function sql($queryString) {
        $starttime = microtime(true);
        
        $result = mysqli_query($this->connection, $queryString);
        
        if ($this->debugMode) {
            $this->queryHistory[] = $queryString . " | " . (microtime(true) - $starttime) . " ms.";
        }
        
        return $result;
    
    }

    /**
     * Return the DAO object based on model name.
     *
     * @param string $modelName            
     *
     * @return DataAccessObject
     */
    public static function dao($modelName) {
        
        // Make sure first letter is uppercase.
        $modelName = ucwords($modelName);
        
        // Look in the dao cache first.
        if (array_key_exists($modelName, self::$daoCache)) {
            return self::$daoCache[$modelName];
        } else {
            
            // Identify whether custom DAO class is decleared.
            $className = "App\\DAO\\{$modelName}DAO";
            if (class_exists($className)) {
                $dao = new $className(self::$ds);
            } else if (class_exists("App\\Model\\{$modelName}")) {
                // If not, just go with the generic one.
                $dao = new GenericDAO(self::$ds, $modelName);
            }
            
            // Put it to the cache.
            self::$daoCache[$modelName] = $dao;
            return $dao;
        }
    
    }

    public function establishConnection() {
        if (! $this->connection) {
            try {
                $this->connection = mysqli_connect($this->host, $this->username, $this->password, $this->database);
            } catch ( \ErrorException $ex ) {
                die("Failed to connect to database: " . $ex->getMessage());
            }
            
            if (mysqli_connect_errno()) {
                throw new \Exception('Failed to connect to MySQL: ' . mysqli_connect_error());
            }
            
            // ensure encoding is ok
            $this->sql("SET NAMES utf8");
            $this->sql("SET CHARACTER SET utf8");
            $this->sql("SET SESSION time_zone = '+0:00'");
        }
    
    }

    private function closeConnection() {
        mysqli_close($this->connection);
    
    }

    public function startTransaction() {
        $this->establishConnection();
        $this->sql("SET AUTOCOMMIT=0");
        $this->sql("START TRANSACTION");
    
    }

    public function rollBack() {
        $this->sql("ROLLBACK");
        $this->sql("SET AUTOCOMMIT=1");
    
    }

    public function commitChanges() {
        $this->sql("COMMIT");
        $this->sql("SET AUTOCOMMIT=1");
    
    }

    /**
     * Run an SQL query against the database
     *
     * @param string $sql            
     * @return mixed
     */
    public function query($sql) {
        $this->establishConnection();
        $result = $this->sql($sql);
        if (! $result) {
            throw new \Exception("Database operation error: " . mysqli_error($this->connection));
        } else {
            return $result;
        }
    
    }

    /**
     *
     * @param string $string            
     * @return string
     */
    public function escape($string) {
        return mysqli_escape_string($this->connection, $string);
    
    }

    public function close() {
        mysqli_close($this->connection);
    
    }

    public function getQueryHistory() {
        return $this->queryHistory;
    
    }

}