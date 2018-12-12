<?php

namespace BatchWrite\Helpers;

use SilverStripe\ORM\DataObject;
use Exception;
use mysqli;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBMoney;

/**
 * Class MySQLiAdapter
 * @package BatchWrite
 */
class MySQLiAdapter implements DBAdapter
{
    /**
     * @var mysqli
     */
    private $conn;

    /**
     * MySQLiAdapter constructor.
     * @param mysqli $conn
     */
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param $sql
     * @param $params
     * @throws Exception
     * @return bool
     */
    public function query($sql, $params)
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Invalid query: '. $sql);
        }

        $refs = array();
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $refs);
        $stmt->execute();
    }

    /**
     * @param $className
     * @param $objects
     * @param bool|false $setID
     * @param bool|false $isUpdate
     * @param string $tablePostfix
     * @throws Exception
     * @return bool
     */
    public function insertClass($className, $objects, $setID = false, $isUpdate = false, $tablePostfix = '')
    {
        $schema = DataObject::getSchema();
        $fields = $schema->databaseFields($className);

        $singleton = singleton($className);

        $fields = array_filter(array_keys($fields), function ($field) use ($schema, $className) {
            return $schema->fieldSpec($className, $field, DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED );
        });

        if ($setID || $isUpdate) {
            array_unshift($fields, 'ID');
        }

        // types need to be set
        $typeLookup = array(
            'ID' => 'i',
        );
        foreach ($fields as $field) {
            $dbObject = $singleton->dbObject($field);
            if ($dbObject instanceof DBBoolean || $dbObject instanceof DBInt) {
                $typeLookup[$field] = 'i';
            } else if ($dbObject instanceof DBFloat || $dbObject instanceof DBDecimal || $dbObject instanceof DBMoney) {
                $typeLookup[$field] = 'd';
            } else {
                $typeLookup[$field] = 's';
            }
        }

        $typeString = '';
        $params = array();
        foreach ($objects as $obj) {
            foreach ($fields as $field) {
                $type = $typeLookup[$field];
                $typeString .= $type;
                $value = $obj->getField($field);
                if ($type === 'i') {
                    $value = intval($value);
                } else if ($type === 'd') {
                    $value = floatval($value);
                } else {
                    $value = '' . $value;
                }
                $params[] = $value;
            }
        }
        array_unshift($params, $typeString);

        $table = $schema->tableName($className) . ($tablePostfix ? '_' . $tablePostfix : '');

        $columns = implode(', ', array_map(function ($name) {
            return "`{$name}`";
        }, $fields));

        // inserts
        $inserts = implode(',', array_fill(0, count($objects), '(' . implode(',', array_fill(0, count($fields), '?')) . ')'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES {$inserts}";

        if ($isUpdate) {
            $mappings = array();
            foreach ($fields as $field) {
                if ($field !== 'ID') {
                    $mappings[] = "`{$field}` = VALUES(`{$field}`)";
                }
            }
            $mappings = implode(',', $mappings);
            $sql .= " ON DUPLICATE KEY UPDATE {$mappings}";
        }

        $this->query($sql, $params);
    }

    /**
     * @param $sql
     * @param $params
     * @return mixed
     */
    public function insertManyMany($sql, $params)
    {
        array_unshift($params, implode('', array_fill(0, count($params), 'i')));
        return $this->query($sql, $params);
    }
}
