<?php
/**
 * Entity manager pattern abstract class
 *
 * @category Abstract
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

namespace abstracts\designPatterns;

use \classes\ExceptionManager as Exception;
use \abstracts\designPatterns\Entity as Entity;
use \abstracts\designPatterns\Collection as Collection;
use \classes\DataBase as DB;
use \classes\IniManager as Ini;

/**
 * Abstract EntityManager pattern
 *
 * @abstract
 */
abstract class EntityManager
{
    /**
     * @var Entity $entity An Entity object
     */
    private $entity;
    /**
     * @var Collection $entityCollection An EntityCollection object
     */
    private $entityCollection;

    /*=====================================
    =            Magic methods            =
    =====================================*/

    /**
     * Constructor that can take an Entity as first parameter and a Collection as second parameter
     *
     * @param Entity     $entity           An entity object DEFAULT null
     * @param Collection $entityCollection A colection oject DEFAULT null
     */
    public function __construct($entity = null, $entityCollection = null)
    {
        if ($entity !== null) {
            $this->setEntity($entity);
        }

        if ($entityCollection !== null) {
            $this->setEntityCollection($entityCollection);
        }
    }

    /*-----  End of Magic methods  ------*/

    /*==========================================
    =            Getters and setter            =
    ==========================================*/

    /**
     * Get the entity object
     *
     * @return Entity The entity object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the entity object
     *
     * @param  Entity    $entity The new entity oject
     * @throws Exception         If the entity is not a subclass of Entity
     */
    public function setEntity($entity)
    {
        if ($entity instanceof Entity) {
            $this->entity = $entity;
        } else {
            throw new Exception('The entity object must be a children of the class "Entity"', Exception::$PARAMETER);
        }
    }

    /**
     * Get the entity collection object
     *
     * @return Collection The entity colection object
     */
    public function getEntityCollection()
    {
        return $this->entityCollection;
    }

    /**
     * Set the entity collection object
     *
     * @param  Collection $entityCollection The new entity collection object
     * @throws Exception                    If the entityCollection is not a subclass of Collection
     */
    public function setEntityCollection($entityCollection)
    {
        if ($entityCollection instanceof Collection) {
            $this->entityCollection = $entityCollection;
        } else {
            throw new Exception(
                'The entityCollection object must be a children of the class "Collection"',
                Exception::$PARAMETER
            );
        }
    }

    /*-----  End of Getters and setter  ------*/

    /*======================================
    =            Public methods            =
    ======================================*/

    /**
     * Save the entity in the database
     *
     * @return boolean True if the entity has been saved or updated else false
     */
    public function saveEntity()
    {
        $sucess = true;

        if ($this->entityAlreadyExists()) {
            $this->updateInDatabase();
        } else {
            $sucess = $this->saveInDatabase();
        }

        return $sucess;
    }

    /**
     * Save the entity colection in the database
     *
     * @return boolean True if the entity collection has been saved else false
     */
    public function saveCollection()
    {
        $currentEntity = $this->entity;
        $success       = true;

        DB::beginTransaction();

        foreach ($this->entityCollection as $entity) {
            if (!$success) {
                break;
            }

            $this->setEntity($entity);
            $success = $this->saveEntity();
        }

        if ($success) {
            DB::commit();
        } else {
            DB::rollBack();
        }

        // restore the initial entity
        $this->entity = $currentEntity;

        return $success;
    }

    /**
     * Delete an entity in the database
     *
     * @return boolean True if the entity has beed deleted else false
     */
    public function deleteEntity()
    {
        return $this->deleteInDatabse();
    }

    /**
     * Drop the entity table in the database
     *
     * @throws Exception If the table is not dropped
     */
    public function dropEntityTable()
    {
        if (!$this->dropTable()) {
            throw new Exception(DB::errorInfo()[2], Exception::$ERROR);
        }
    }

    /**
     * Create the entity table in the database
     *
     * @throws Exception If the table is not created
     */
    public function createEntityTable()
    {
        if (!$this->createTable()) {
            throw new Exception(DB::errorInfo()[2], Exception::$ERROR);
        }
    }

    /*-----  End of Public methods  ------*/

    /*=======================================
    =            Private methods            =
    =======================================*/

    /**
     * Check if the entity already exists in the database
     *
     * @return boolean True if the entity exists else false
     */
    private function entityAlreadyExists()
    {
        $sqlMarks = " SELECT COUNT(*)\n FROM %s\n WHERE %s";

        $sql = $this->sqlFormater(
            $sqlMarks,
            $this->entity->getTableName(),
            $this->getEntityPrimaryKeysWhereClause()
        );

        return ((int) DB::query($sql)->fetchColumn() >= 1);
    }

    /**
     * Save the entity in the database
     *
     * @return boolean True if the entity has beed saved else false
     */
    private function saveInDatabase()
    {
        $sqlMarks = " INSERT INTO %s\n VALUES %s";

        $sql = $this->sqlFormater(
            $sqlMarks,
            $this->entity->getTableName(),
            $this->getEntityAttributesMarks($this->entity)
        );

        return DB::prepare($sql)->execute(array_values($this->entity->getColumnsValue()));
    }

    /**
     * Uddape the entity in the database
     *
     * @return integer The number of rows updated
     */
    private function updateInDatabase()
    {
        $sqlMarks = " UPDATE %s\n SET %s\n WHERE %s";

        $sql = $this->sqlFormater(
            $sqlMarks,
            $this->entity->getTableName(),
            $this->getEntityUpdateMarksValue(),
            $this->getEntityPrimaryKeysWhereClause()
        );

        return (int) DB::exec($sql);
    }

    /**
     * Delete the entity from the database
     *
     * @return boolean True if the entity has beed deleted else false
     */
    private function deleteInDatabse()
    {
        $sqlMarks = " DELETE FROM %s\n WHERE %s";

        $sql = $this->sqlFormater(
            $sqlMarks,
            $this->entity->getTableName(),
            $this->getEntityPrimaryKeysWhereClause()
        );

        return ((int) DB::exec($sql) === 1);
    }

    /**
     * Drop the entity table
     *
     * @return boolean True if the table is dropped else false
     */
    private function dropTable()
    {
        $sql = 'DROP TABLE `' . $this->entity->getTableName() . '`;';

        return DB::exec($sql) !== false;
    }

    /**
     * Create a table based on the entity ini conf file
     *
     * @return boolean True if the table is created else false
     */
    private function createTable()
    {
        $columns     = array();
        $comment     = 'AUTO GENERATED THE ' . date('Y-m-d H:i:s');
        $sql         = 'CREATE TABLE `' . $this->entity->getTableName() . '` (';

        foreach ($this->entity->getColumnsAttributes() as $columnName => $columnAttributes) {
            $columns[] = $this->createColumnDefinition($columnName, $columnAttributes);
        }

        $sql .= implode(', ', $columns);
        $sql .= $this->createTableConstraints() . PHP_EOL;
        $sql .= ') ENGINE = ' . $this->entity->getEngine();

        if ($this->entity->getCharset() !== '') {
            $sql .= ', CHARACTER SET = ' . $this->entity->getCharset();
        }

        if ($this->entity->getCollation() !== '') {
            $sql .= ', COLLATE = ' . $this->entity->getCollation();
        }

        if ($this->entity->getComment() !== '') {
            $comment .= ' | ' . $this->entity->getComment();
        }
        
        $sql .= ', COMMENT = \'' . $comment . '\'';

        return DB::exec($sql . ';') !== false;
    }

    /*==========  Utilities methods  ==========*/

    /**
     * Get the "?" markers of the entity
     *
     * @return string The string markers (?, ?, ?)
     */
    private function getEntityAttributesMarks()
    {
        return '(' . implode(array_fill(0, count($this->entity->getColumnsAttributes()), '?'), ', ') . ')';
    }

    /**
     * Get the "columnName = 'columnValue'" markers of the entity for the update sql command
     *
     * @return string The string markers (columnName1 = 'value1', columnName2 = 'value2') primary keys EXCLUDED
     */
    private function getEntityUpdateMarksValue()
    {
        $marks = array();

        foreach ($this->entity->getColumnsKeyValueNoPrimary() as $columnName => $columnValue) {
            $marks[] = $columnName . ' = ' . DB::quote($columnValue);
        }

        return implode(', ', $marks);
    }

    /**
     * Get the "primaryKey1 = 'primaryKey1Value' AND primaryKey2 = 'primaryKey2Value'" of the entity
     *
     * @return string The SQL segment string "primaryKey1 = 'primaryKey1Value' AND primaryKey2 = 'primaryKey2Value'"
     */
    private function getEntityPrimaryKeysWhereClause()
    {
        $columnsValue = array();

        foreach ($this->entity->getIdKeyValue() as $columnName => $columnValue) {
            $columnsValue[] = $columnName . ' = ' . DB::quote($columnValue);
        }

        return implode($columnsValue, 'AND ');
    }

    /**
     * Utility method to set and return a column definition to put in a SQL create table query
     *
     * @param  string $columnName       The column name
     * @param  array  $columnAttributes The columns attributes
     * @return string                   The formatted string to put in a SQL create table query
     */
    private function createColumnDefinition($columnName, $columnAttributes)
    {
        $col = PHP_EOL . "\t`" . $columnName . '` ' . $columnAttributes['type'];

        if (isset($columnAttributes['size'])) {
            $col .= '(' . $columnAttributes['size'] . ')';
        }

        if (isset($columnAttributes['unsigned'])) {
            $col .= ' UNSIGNED';
        }

        if ($columnAttributes['isNull']) {
            $col .= ' NULL';
        } else {
            $col .= ' NOT NULL';
        }

        if (isset($columnAttributes['default'])) {
            $col .= ' DEFAULT '
                . ($columnAttributes['default'] === 'NULL' ? 'NULL' : '\'' . $columnAttributes['default'] . '\'');
        }

        if (isset($columnAttributes['autoIncrement'])) {
            $col .= ' AUTO_INCREMENT';
        }

        if (isset($columnAttributes['comment'])) {
            $col .= ' COMMENT \'' . $columnAttributes['comment'] . '\'';
        }

        if (isset($columnAttributes['storage'])) {
            $col .= ' STORAGE ' . $columnAttributes['storage'];
        }

        return $col;
    }

    /**
     * Utility method to set en return the table constraints to put in a SQL create table query
     *
     * @return string The formatted string to put in a SQL create table query
     */
    private function createTableConstraints()
    {
        $constraints = $this->entity->getConstraints();
        $sql = '';

        if (isset($constraints['primary'])) {
            $sql .= ',' . PHP_EOL . "\tCONSTRAINT `" . $constraints['primary']['name'] . '`';
            $sql .= ' PRIMARY KEY (' . $constraints['primary']['columns'] . ')';
        }

        if (isset($constraints['unique'])) {
            $sql .= ',' . PHP_EOL . "\tUNIQUE `" . $this->entity->getTableName() . '_unique_constraint`';
            $sql .= ' (' . $constraints['unique'] . ')';
        }

        if (isset($constraints['foreignKey'])) {
            $names = array_keys($constraints['foreignKey']);

            foreach ($names as $name) {
                $sql .= ',' . PHP_EOL . "\tCONSTRAINT `" . $name . '`';
                $sql .= ' FOREIGN KEY (' . $constraints['foreignKey'][$name]['columns'] . ')';
                $sql .= PHP_EOL . "\t\tREFERENCES `" . $constraints['foreignKey'][$name]['tableRef'] . '`';
                $sql .= '(' . $constraints['foreignKey'][$name]['columnsRef'] . ')';

                if (isset($constraints['foreignKey'][$name]['match'])) {
                    $sql .= PHP_EOL . "\t\tMATCH " . $constraints['foreignKey'][$name]['match'];
                }

                if (isset($constraints['foreignKey'][$name]['onDelete'])) {
                    $sql .= PHP_EOL . "\t\tON DELETE " . $constraints['foreignKey'][$name]['onDelete'];
                }

                if (isset($constraints['foreignKey'][$name]['onUpdate'])) {
                    $sql .= PHP_EOL . "\t\tON UPDATE " . $constraints['foreignKey'][$name]['onUpdate'];
                }
            }
        }

        return $sql;
    }

    /**
     * Format a sql query with sprintf function
     * First arg must be the sql string with markers (%s, %d, ...)
     * Others args should be the values for the markers
     *
     * @return string The SQL formated string
     */
    private function sqlFormater()
    {
        return call_user_func_array('sprintf', func_get_args());
    }

    /*-----  End of Private methods  ------*/
}
