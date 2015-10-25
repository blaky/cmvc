<?php

namespace CacaoFw\Service;

use CacaoFw\DataAccessObject;
use CacaoFw\GenericDAO;


class DataService {

    private static $daoCache = array();

    private static $ds;

    private $host;

    private $database;

    private $username;

    private $password;

    private $debugMode;

    public $connection;

    public function __construct($config) {
        $this->host = $config["db.host"];
        $this->database = $config["db.database"];
        $this->username = $config["db.username"];
        $this->password = $config["db.password"];

        $this->debugMode = !!$config["debug"];
        $this->establishConnection();
        self::$ds = $this;
    }

    /**
     * Return the DAO object based on model name.
     *
     * @param string $modelName
     *
     * @return DataAccessObject
     */
    public static function dao($modelName) {
        if (array_key_exists($modelName, self::$daoCache)) {
            return self::$daoCache[$modelName];
        } else {
            $className = "App\\DAO\\{$modelName}DAO";
            if (class_exists($className)) {
                $dao = new $className(self::$ds);
            } else if (class_exists("App\\Model\\{$modelName}")) {
                $dao = new GenericDAO(self::$ds, $modelName);
            }

            self::$daoCache[$modelName] = $dao;
            return $dao;
        }
    }

    public function establishConnection() {
        if (!$this->connection) {
            try {
                $this->connection = mysqli_connect($this->host, $this->username, $this->password,
                        $this->database);
            } catch (\ErrorException $ex) {
                die("Failed to connect to database: " . $ex->getMessage());
            }

            if (mysqli_connect_errno()) {
                throw new \Exception('Failed to connect to MySQL: ' . mysqli_connect_error());
            }

            // ensure encoding is ok
            mysqli_query($this->connection, "SET NAMES utf8");
            mysqli_query($this->connection, "SET CHARACTER SET utf8");
            mysqli_query($this->connection, "SET SESSION time_zone = '+0:00'");
        }
    }

    private function closeConnection() {
        mysqli_close($this->connection);
    }

    public function startTransaction() {
        $this->establishConnection();
        mysqli_query($this->connection, "SET AUTOCOMMIT=0");
        mysqli_query($this->connection, "START TRANSACTION");
    }

    public function rollBack() {
        mysqli_query($this->connection, "ROLLBACK");
        mysqli_query($this->connection, "SET AUTOCOMMIT=1");
    }

    public function commitChanges() {
        mysqli_query($this->connection, "COMMIT");
        mysqli_query($this->connection, "SET AUTOCOMMIT=1");
    }

    /**
     * Run an SQL query against the database
     *
     * @param string $sql
     * @return mixed
     */
    public function query($sql) {
        $this->establishConnection();
        if ($this->debugMode) {
            echo "<pre>SQL query: " . $sql . "</pre>";
        }

        $result = mysqli_query($this->connection, $sql);
        if (!$result) {
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
}