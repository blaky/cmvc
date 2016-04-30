<?php

namespace CacaoFw;

define('MODEL_PATH', "App\\Model\\");
/**
 *
 * @author Bence
 *
 */
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
    public $ds;

    public function __construct(\CacaoFw\Service\DataService $ds, $baseClassName = null) {
        $this->ds = $ds;

        if (is_null($baseClassName)) {
            $baseClassName = substr(str_replace("DAO", "", get_class($this)), 5);
        }

        $this->className = MODEL_PATH . $baseClassName;
        $this->tableName = strtolower($baseClassName);

    }

    /**
     * Gerates the core of an INSERT statement,
     *
     * @param array $array
     *            Values to be inserted.
     */
    private function generateInsertValues($array) {
        $preparedValues = array();
        foreach ( $array as $key => $value ) {
            if ($key != "id") {
                $formattedvalue = $this->formatValue($value);
                if ($formattedvalue !== false)
                    $preparedValues[] = $formattedvalue;
            }
        }

        return join($preparedValues, ",");

    }

    /**
     * Ensure that we deal with the right type of object.
     */
    private function checkObject($object) {
        if (get_class($object) !== $this->className) {
            throw new Exception("Invalid object type: " . get_class($object) . ", expected: " . $this->className);
        }

    }

    /**
     * Escapes and formats value that suits UPDATE and INSERT statement
     * standards.
     *
     * @param mixed $value
     *            Value to be formated.
     * @throws InvalidArgumentException if the datatype is not supported.
     * @return string SQL frienly string.
     */
    private function formatValue($value) {
        $type = gettype($value);
        $stringVal;
        switch ($type) {
            case "boolean" :
                return $value ? "1" : "0";
            case "NULL" :
                return "NULL";
            case "double" :
            case "integer" :
                return $value . "";
            case "string" :
                return "'" . $this->ds->escape($value) . "'";
            case "object" :
            case "array" :
                return false;
            default :
                throw new \InvalidArgumentException("Cannot handle datatype: " . $type);
        }

    }

    /**
     * Wrap raw database result array to container object.
     *
     * @param array $parameters
     *            Database row.
     * @param string $class
     *            Full path of container object.
     *
     * @return AbstractModel Populated object.
     */
    protected function wrapIntoObject($parameters, $class = null) {
        if (is_null($class)) {
            $class = $this->className;
        }
        $reflection = new \ReflectionClass($class);
        $object = $reflection->newInstanceArgs($parameters);
        return $object;

    }

    private function getObjects($condition = false, $deep = true, $order = false) {
        // Create query string and inspect target object.
        $query = "SELECT * FROM `" . $this->tableName . "` ";
        if ($deep) {
            $class = new \ReflectionClass($this->className);
            $columnCounts = array();
            // Load one to many relationships.
            $childLinks = $class->getStaticPropertyValue('links');

            // Look for many to one
            $properties = $class->getProperties();
            $columnCounts[] = count($properties);
            $parentLinks = array();
            foreach ( $properties as $propertyName ) {
                if (preg_match("/[a-z]+id/", $propertyName->getName())) {
                    $parentLinks[] = substr($propertyName->getName(), 0, - 2);
                }
            }

            $links = array_merge($childLinks, $parentLinks);

            // Inspect linking tables.
            foreach ( $links as $linkingTable ) {
                // Get the count of properties, which will be used for wrapping.
                $class = new \ReflectionClass(MODEL_PATH . ucfirst($linkingTable));
                $columnCounts[] = count($class->getProperties());
            }

            // Join child objects.
            foreach ( $childLinks as $linkingTable ) {
                $query .= " JOIN {$linkingTable} ";
                $query .= " ON {$linkingTable}.{$this->tableName}id = {$this->tableName}.id";
            }

            // Join parent objects.
            foreach ( $parentLinks as $linkingTable ) {
                $query .= " JOIN {$linkingTable} ";
                $query .= " ON {$linkingTable}.id = {$this->tableName}.{$linkingTable}id";
            }
        }

        // Append condition.
        if ($condition) {
            $query .= " WHERE {$condition} ";
        }

        // Append order.
        if ($order) {
            $query .= "ORDER BY {$order} ";
        }
        $coreObjects = array();

        // Execute the query and wrap data into objects.
        $this->ds->establishConnection();
        $result = $this->ds->query($query);
        while ( $row = mysqli_fetch_array($result) ) {
            ksort($row, SORT_NATURAL);
            if (! array_key_exists($row[0], $coreObjects)) {
                $coreObjects[$row[0]] = $this->wrapIntoObject($row);
            }
            if ($deep) {
                $i = 0;
                $coreObject = $coreObjects[$row[0]];
                $columnCounter = 0;
                foreach ( $links as $linkingTable ) {
                    // Increase column count.
                    $columnCounter += $columnCounts[$i];
                    $i ++;

                    // Linking table class.
                    $classname = MODEL_PATH . ucfirst($linkingTable);
                    // Linking table values.
                    $values = array_slice($row, $columnCounter - 1);

                    if (in_array($linkingTable, $childLinks)) {
                        // Create a holder array on the parent object if has not been there.
                        if (! isset($coreObject->{$linkingTable})) {
                            $coreObject->{$linkingTable} = array();
                        }

                        // Push the populated child object to the array.
                        $coreObject->{$linkingTable}[] = $this->wrapIntoObject($values, $classname);
                    } else {
                        // Declare a new property on the object.
                        $coreObject->{$linkingTable} = $this->wrapIntoObject($values, $classname);
                    }
                }
            } else {
                $coreObjects[] = $this->wrapIntoObject($row);
            }
        }
        return $coreObjects;

    }

    /**
     * Finds the object by its ID field.
     *
     * @param int $id
     *            The object ID.
     * @param bool $deep
     *            Deep fetch object.
     * @return mixed|NULL
     */
    public function byId($id, $deep = true) {
        $condition = $this->tableName . ".id = " . $this->ds->escape($id);
        $result = $this->getObjects($condition, $deep);
        if (count($result) > 0) {
            return array_values($result)[0];
        } else {
            return null;
        }

    }

    public function listAll($order = null, $deep = true) {
        return $this->getObjects(false, $deep, $order);

    }

    public function listByCondition($condition) {
        $this->ds->establishConnection();

        $objects = array();
        $result = $this->ds->query("SELECT * FROM `{$this->tableName}` WHERE $condition");
        while ( $row = mysqli_fetch_array($result) ) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;

    }

    public function getListByField($field, $value) {
        $this->ds->establishConnection();
        $objects = array();
        $condition = "`{$this->tableName}`.`" . $field . "` = {$this->formatValue($value)}";
        while ( $row = mysqli_fetch_array($result) ) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;

    }

    public function getListByFieldList($field, $list = array()) {
        $formattedValueList = array();
        foreach ( $list as $value ) {
            $formattedValueList[] = $this->formatValue($value);
        }

        $this->ds->establishConnection();
        $objects = array();
        $result = $this->ds->query("SELECT * FROM `{$this->tableName}` WHERE `" . $field . "` IN (" . join(',', $formattedValueList) . ")");
        while ( $row = mysqli_fetch_array($result) ) {
            ksort($row, SORT_STRING);
            $id = intval($row["id"]);
            $objects[$id] = $this->wrapIntoObject($row);
        }
        return $objects;

    }

    public function getEntryByField($field, $value) {
        $query = "SELECT * FROM `{$this->tableName}` WHERE `" . $field . "` = " . $this->formatValue($value);
        $row = mysqli_fetch_row($this->ds->query($query));
        if ($row) {
            return $this->wrapIntoObject($row);
        } else {
            return null;
        }

    }

    /**
     * Removes entity based on the ID
     *
     * @param int $id
     *            ID of the entity to be removed.
     * @param bool $deep
     *            Deep clean of child objects.
     */
    public function delete($id, $deep = true) {
        if ($deep) {
            $class = new \ReflectionClass($this->className);
            $childLinks = $class->getStaticPropertyValue('links');
            foreach ( $childLinks as $link ) {
                // TODO: find grandchildren and remove them too!
                $query = "DELETE FROM `{$link}` WHERE `{$this->tableName}id` = " . $this->ds->escape($id);
                $this->ds->query($query);
            }
        }

        $query = "DELETE FROM `{$this->tableName}` WHERE `id` = " . $this->ds->escape($id);
        $this->ds->query($query);

    }

    /**
     * Persist an object and sets the id
     *
     * @param unknown $object
     */
    public function createObject($object) {
        $this->create($object);
        // Fetch the ID
        $object->id = mysqli_insert_id($this->ds->connection);
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
        $sql .= "VALUES ({$this->generateInsertValues($array)})";
        $this->ds->query($sql);

    }

    /**
     * This function should implement an update operation using an UPDATE query
     *
     * @param object $object
     */
    public function update($object) {
        $this->checkObject($object);

        $sql = "UPDATE `{$this->tableName}` SET ";
        foreach ( get_object_vars($object) as $column => $value ) {
            $formattedValue = $this->formatValue($value);
            if ($column != "id" && $formattedValue) {
                $sql .= " `$column` = " . $formattedValue . ",";
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " WHERE id = " . intval($object->id);
        $this->ds->query($sql);

    }

}