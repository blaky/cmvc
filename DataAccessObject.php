<?php

namespace CacaoFw;

abstract class DataAccessObject {

    /**
     *
     * @var string
     */
    private $className;

    /**
     *
     * @var string
     */
    protected $tableName;

    /**
     *
     * @var \CacaoFw\Service\DataService
     */
    public $db;

    public function __construct(\CacaoFw\Service\DataService $db, $baseClassName = null) {
        $this->db = $db;
        
        if (is_null($baseClassName)) {
            $baseClassName = substr(str_replace("DAO", "", get_class($this)), 5);
        }
        
        $this->className = "App\\Model\\{$baseClassName}";
        $this->tableName = strtolower($baseClassName);
    }

    private function generateInsertValues($array) {
        $preparedValues = array();
        foreach ($array as $key => $value) {
            if ($key != "id") {
                $preparedValues[] = $this->formatValue($value);
            }
        }
        
        return join($preparedValues, ",");
    }

    private function checkObject($object) {
        if (get_class($object) !== $this->className) {
            throw new Exception("Invalid object type: " . get_class($object) . ", expected: " . $this->className);
        }
    }

    private function formatValue($value) {
        $type = gettype($value);
        $stringVal;
        switch ($type) {
            case "boolean" :
                $stringVal = $value ? "1" : "0";
                break;
            case "NULL" :
                $stringVal = "NULL";
                break;
            case "double" :
            case "integer" :
                $stringVal = $value . "";
                break;
            case "string" :
                $stringVal = "'" . $this->db->escape($value) . "'";
                break;
            default :
                throw new Exception("Cannot handle datatype: " . $type);
        }
        return $stringVal;
    }

    protected function wrapIntoObject($parameters) {
        $reflection = new \ReflectionClass($this->className);
        $object = $reflection->newInstanceArgs($parameters);
        return $object;
    }

    public function byId($id) {
        $this->db->establishConnection();
        $query = "SELECT * FROM `" . $this->tableName . "` WHERE id = " . $this->db->escape($id);
        $row = mysqli_fetch_row($this->db->query($query));
        if ($row) {
            return $this->wrapIntoObject($row);
        } else {
            return null;
        }
    }

    public function listAll($orderBy = null) {
        $this->db->establishConnection();
        
        $objects = array();
        $result = $this->db->query("SELECT * FROM `{$this->tableName}`" . ($orderBy != null ? ' ORDER BY ' . $orderBy : ''));
        while ($row = mysqli_fetch_array($result)) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;
    }

    public function listByCondition($condition) {
        $this->db->establishConnection();
        
        $objects = array();
        $result = $this->db->query("SELECT * FROM `{$this->tableName}` WHERE $condition");
        while ($row = mysqli_fetch_array($result)) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;
    }

    public function getListByField($field, $value) {
        $this->db->establishConnection();
        $objects = array();
        $result = $this->db->query("SELECT * FROM `{$this->tableName}` WHERE `" . $field . "` = " . $this->formatValue($value));
        while ($row = mysqli_fetch_array($result)) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;
    }

    public function getListByFieldList($field, $list = array()) {
        $formattedValueList = array();
        foreach ($list as $value) {
            $formattedValueList[] = $this->formatValue($value);
        }
        
        $this->db->establishConnection();
        $objects = array();
        $result = $this->db->query(
                "SELECT * FROM `{$this->tableName}` WHERE `" . $field . "` IN (" . join(',', $formattedValueList) . ")");
        while ($row = mysqli_fetch_array($result)) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;
    }

    public function getEntryByField($field, $value) {
        $query = "SELECT * FROM `{$this->tableName}` WHERE `" . $field . "` = " . $this->formatValue($value);
        $row = mysqli_fetch_row($this->db->query($query));
        if ($row) {
            return $this->wrapIntoObject($row);
        } else {
            return null;
        }
    }

    /**
     * Removes entity based on the id
     *
     * @param int $id
     */
    public function delete($id) {
        $query = "DELETE FROM `{$this->tableName}` WHERE `id` = " . $this->db->escape($id);
        $this->db->query($query);
    }

    /**
     * Persist an object and sets the id
     *
     * @param unknown $object
     */
    public function createObject($object) {
        $this->create($object);
        $object->id = mysqli_insert_id($this->db->connection);
        return $object;
    }

    /**
     * This function should implement a create operation using an INSERT query
     *
     * @param object $object
     * @return void;
     */
    public function create($object) {
        $this->checkObject($object);
        $array = get_object_vars($object);
        $fields = array_keys($array);
        
        if (($key = array_search("id", $fields)) !== false) {
            unset($fields[$key]);
        }
        
        $sql = "INSERT INTO `{$this->tableName}` (`" . join($fields, '`,`') . '`) ';
        $sql .= 'VALUES (' . $this->generateInsertValues($array) . ')';
        $this->db->query($sql);
    }

    /**
     * This function should implement an update operation using an UPDATE query
     *
     * @param object $object
     */
    public function update($object) {
        $this->checkObject($object);
        
        $sql = "UPDATE `{$this->tableName}` SET ";
        foreach (get_object_vars($object) as $column => $value) {
            if ($column != "id") {
                $sql .= " `$column` = " . $this->formatValue($value) . ",";
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " WHERE id = " . intval($object->id);
        $this->db->query($sql);
    }
}