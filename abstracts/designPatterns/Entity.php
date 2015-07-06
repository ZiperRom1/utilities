<?php

namespace utilities\abstracts\designPatterns;

use \utilities\classes\exception\ExceptionManager as Exception;
use \utilities\classes\ini\IniManager as Ini;
use \utilities\classes\DataBase as DB;

abstract class Entity
{
    /**
     * @var array $columnsValue An assosiative array with colum name on key and its value on value
     */
    const ENTITIES_CONF_PATH = 'database/entities/';

    private $conf;
    private $tableName;
    private $entityName;
    private $id;
    private $maxColumnNameSize = 0;
    private $maxColumnTypeSize = 0;

    protected $columnsValue      = array();
    protected $columnsAttributes = array();

    /*=====================================
    =            Magic methods            =
    =====================================*/

    public function __construct($entityName)
    {
        Ini::setIniFileName(self::ENTITIES_CONF_PATH . $entityName . '.ini');

        $this->conf       = Ini::getAllParams();
        $this->entityName = $entityName;
        $this->parseConf();
    }

    public function __isset($columnName)
    {
        return array_key_exists($columnName, $this->columnsValue);
    }

    public function __get($columnName)
    {
        if (strtolower($columnName) === 'id') {
            $value = $this->getId();
        } else {
            if (!$this->__isset($columnName)) {
                throw new Exception('The attribute ' . $columnName . ' is undefined', Exception::$PARAMETER);
            }

            $value = $this->columnsValue[$columnName];
        }

        return $value;
    }

    public function __set($columnName, $value)
    {
        if (strtolower($columnName) === 'id') {
            $this->setId($value);
        } else {
            if (!$this->__isset($columnName)) {
                throw new Exception('The attribute ' . $columnName . ' is undefined', Exception::$PARAMETER);
            }
        
            $this->columnsValue[$columnName] = $value;
        }
    }

    public function __toString()
    {
        $this->setBeautifullIndent();

        $string = '['  . $this->entityName . ']' . PHP_EOL;

        foreach ($this->columnsValue as $columnName => $columnValue) {
            $string .=
                '  ' . $this->smartIndent($columnName, $this->maxColumnNameSize)
                . '  ' . $this->smartIndent(
                    $this->columnsAttributes[$columnName]['type'] . '(' .
                    $this->columnsAttributes[$columnName]['size'] . ')',
                    $this->maxColumnTypeSize
                )
                . '  = ' . $this->formatValue($columnValue) . PHP_EOL;
        }

        return $string;
    }

    /**
     * Return the entity in an array format
     *
     * @return array Array with columns name on keys and columns value on values
     */
    public function __toArray()
    {
        return $this->columnsValue;
    }

    public function __debugInfo()
    {
        return $this->columnsValue;
    }

    /*-----  End of Magic methods  ------*/

    /*==========================================
    =            Getters and setter            =
    ==========================================*/

    /**
     * Get the key id of an entity
     *
     * @return string[] The entity key id
     */
    public function getIdKey()
    {
        $idKey = array();

        if (is_array($this->id)) {
            foreach ($this->id as $columnName) {
                $idKey[] = $columnName;
            }
        } else {
            $idKey[] = $this->id;
        }

        return $idKey;
    }

    /**
     * Get the id value of the entity
     *
     * @return int[] The id value
     */
    public function getIdValue()
    {
        $idValue = array();

        if (is_array($this->id)) {

            foreach ($this->id as $columnName) {
                $idValue[] = $this->__get($columnName);
            }
        } else {
            $idValue[] = $this->__get($this->id);
        }

        return $idValue;
    }

    /**
     * Get the associative array idKey => idValue
     *
     * @return array The associative array idKey => idValue
     */
    public function getIdKeyValue()
    {
        $idKeyValue = array();

        if (is_array($this->id)) {

            foreach ($this->id as $columnName) {
                $idKeyValue[$columnName] = $this->__get($columnName);
            }
        } else {
            $idKeyValue[$this->id] = $this->__get($this->id);
        }

        return $idKeyValue;
    }

    /**
     * Set the id value of the entity (can be an array if several primary keys)
     *
     * @param int|array The id value
     */
    public function setId($value)
    {
        if (is_array($this->id)) {
            if (!is_array($value)) {
                throw new Exception(
                    'The id is on several columns you must passed an assosiative array with keys (' .
                    implode(', ', $this->id) . ')',
                    Exception::$PARAMETER
                );
            }

            foreach ($value as $key => $val) {
                if (!array_key_exists($key, $this->columnsValue)) {
                    throw new Exception(
                        'The keys of the assosiative array must be one of these : ' . implode(', ', $this->id),
                        Exception::$PARAMETER
                    );
                }
                $this->__set($key, $val);
            }
        } else {
            $this->__set($this->id, $val);
        }
    }

    /**
     * Get the entity table name
     *
     * @return string The entity table name
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    public function getColumnsAttributes()
    {
        return $this->columnsAttributes;
    }

    public function getColumsValue()
    {
        return $this->columnsValue;
    }

    /*-----  End of Getters and setter  ------*/

    /**
     * Parse an entity conf to extract attributes
     */
    private function parseConf()
    {
        $columnsValue = array();

        foreach ($this->conf as $columnName => $columnAttributes) {
            if ($columnName !== 'table') {
                $columnsValue[$columnName]      = null;
                $columnsAttributes[$columnName] = $columnAttributes;
            } else {
                $this->tableName = $columnAttributes['name'];

                if (isset($columnAttributes['primaryKey'])) {
                    $this->id = $columnAttributes['primaryKey'];
                }
            }
        }

        $this->columnsValue      = $columnsValue;
        $this->columnsAttributes = $columnsAttributes;
    }

    /**
     * Utility method to process and set the max size of name and type
     */
    private function setBeautifullIndent()
    {
        $maxColumnNameSize = 0;
        $maxColumnTypeSize = 0;

        foreach ($this->columnsAttributes as $columnName => $columnAttributes) {
            $currentColumnNameSize = strlen($columnName);
            $currentColumnTypeSize = strlen($columnAttributes['type']) + strlen($columnAttributes['size']);

            if ($currentColumnNameSize > $maxColumnNameSize) {
                $maxColumnNameSize = $currentColumnNameSize;
            }

            if ($currentColumnTypeSize > $maxColumnTypeSize + 2) {
                $maxColumnTypeSize = $currentColumnTypeSize + 2;
            }
        }

        $this->maxColumnNameSize = $maxColumnNameSize;
        $this->maxColumnTypeSize = $maxColumnTypeSize;
    }

    /**
     * Indent a string nicely to align "=" sign
     *
     * @param  string $value   The string to indent
     * @param  int    $maxSize The max size of all the string to indent with
     * @return string          The indented string
     */
    private function smartIndent($value, $maxSize)
    {
        return str_pad($value, $maxSize, ' ', STR_PAD_RIGHT);
    }

    /**
     * Cast a SQL return value to a string value
     *
     * @param  mixed $value The typed value
     * @return string       The string value
     */
    private function formatValue($value)
    {
        $formatedValue;

        switch (gettype($value)) {
            case 'boolean':
                $formatedValue = $value ? 'TRUE' : 'FALSE';
                break;
            
            case 'integer':
                $formatedValue = (int) $value;
                break;

            case 'string':
                $formatedValue = '"' . $value . '"';
                break;

            case 'NULL':
                $formatedValue = 'NULL';
                break;

            default:
                $formatedValue = $value;
                break;
        }

        return $formatedValue;
    }
}
