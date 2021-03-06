<?php
/**
 * Singleton database manager
 *
 * @category Singleton
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 * @example /utilities/examples/dataBase.php                  Basic use of this singleton
 */

namespace classes;

use \classes\ExceptionManager as Exception;
use \classes\IniManager as Ini;
// WARNING if you change the path below, change it in the initialize method in the setAttribute call
use \classes\PDOStatementCustom as PDOStatementCustom;

/**
 * Singleton pattern style to handle DB connection using PDO
 *
 * @class Database
 *
 * PDO methods that can be called directly with the __callStatic magic method
 *
 * @method bool          beginTransaction()                                              Initiates a transaction
 * @method bool          commit()                                                        Commits a transaction
 * @method mixed         errorCode()                                                     Fetch the SQLSTATE associated with the last operation on the database handle
 * @method array         errorInfo()                                                     Fetch extended error information associated with the last operation on the database handle
 * @method int           exec(string $statement)                                         Execute an SQL statement and return the number of affected rows
 * @method mixed         getAttribute(int $attribute)                                    Retrieve a database connection attribute
 * @method array         getAvailableDrivers()                                           Return an array of available PDO drivers
 * @method bool          inTransaction()                                                 Checks if inside a transaction
 * @method string        lastInsertId(string $name = NULL)                               Returns the ID of the last inserted row or sequence value
 * @method \PDOStatement prepare(string $statement, array $driver_options = array())     Prepares a statement for execution and returns a statement object
 * @method \PDOStatement query(string $statement)                                        Executes an SQL statement, returning a result set as a PDOStatement object
 * @method string        quote(string $string, int $parameter_type = PDO::PARAM_STR )    Quotes a string for use in a query
 * @method bool          rollBack()                                                      Rolls back a transaction
 * @method bool          setAttribute(int $attribute , mixed $value)                     Set an attribute
 */
class DataBase
{
    use \traits\EchoTrait;

    /**
     * @const INI_CONF_FILE The path of the ini configuration file
     */
    const INI_CONF_FILE = 'conf.ini';

    /**
     * @var \PDO $PDO A PDO object DEFAULT null
     */
    private static $PDO = null;
    /**
     * @var boolean $printSql If the SQL requests should be printed in a console DEFAULT null
     */
    private static $printSQL = null;

    /*=====================================
    =            Magic methods            =
    =====================================*/

    /**
     * A never called constructor (can't declare it private because it's generate error)
     */
    public function __construct()
    {
    }

    /**
     * Is triggered when invoking inaccessible methods in a static context
     *
     * @param  string    $name      Name of the method being called
     * @param  array     $arguments Enumerated array containing the parameters passed to the method called
     * @throws Exception            If the method called is not a PDO method
     * @static
     * @note                        This is so powerfull, we can call non static methods with a static call
     */
    public static function __callStatic($name, $arguments = array())
    {
        $PDO = new \ReflectionClass('\PDO');

        static::initialize();

        if ($PDO->hasMethod($name)) {
            if (static::$printSQL && ($name === 'query' || $name === 'exec')) {
                static::out(PHP_EOL . $arguments[0] . PHP_EOL);
            }

            return call_user_func_array(array(static::$PDO, $name), $arguments);
        } else {
            throw new Exception('The method "' . $name . '" is not a PDO method', Exception::$PARAMETER);
        }
    }

    /*-----  End of Magic methods  ------*/

    /*==================================================
    =            Getters / setters (static)            =
    ==================================================*/
    
    /**
     * Get the printSQL value
     *
     * @return boolean The printSQL value
     * @static
     */
    public static function getPrintSQL()
    {
        return static::$printSQL;
    }

    /**
     * Set the printSQL value
     *
     * @param  boolean   $printSQL The printSQL value
     * @throws Exception           If the printSQL value is not a boolean
     * @static
     */
    public static function setPrintSQL($printSQL)
    {
        if (is_bool($printSQL)) {
            static::$printSQL = $printSQL;
            static::setPDOStatement();
        } else {
            throw new Exception(
                'The printSQL value must be a boolean',
                Exception::$PARAMETER
            );
        }
    }
    
    /*-----  End of Getters / setters (static)  ------*/

    /*======================================
    =            Public methods            =
    ======================================*/

    /**
     * Get all the table name of he current database
     *
     * @return string[] The table name as a string array
     * @static
     */
    public static function getAllTables()
    {
        static::initialize();

        return static::$PDO->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Delete all the rows of a table
     *
     * @param  string  $tableName The table name to clean
     * @return boolean            True on success else false
     * @static
     */
    public static function cleanTable($tableName)
    {
        static::initialize();

        return static::$PDO->exec('DELETE FROM ' . $tableName) !== false;
    }

    /**
     * Drop a table
     *
     * @param  string  $tableName The table name to drop
     * @return boolean            True on success else false
     * @static
     */
    public static function dropTable($tableName)
    {
        static::initialize();

        return static::$PDO->exec('DROP TABLE ' . $tableName) !== false;
    }

    /**
     * Show all the table data with limit default (0, 100)
     *
     * @param  string  $tableName The table name
     * @param  integer $begin     Data start at this index DEFAULT 0
     * @param  integer $end       Data stop at this index DEFAULT 100
     * @return array              Array containing the result
     * @static
     */
    public static function showTable($tableName, $begin = 0, $end = 100)
    {
        static::initialize();

        $sqlMarks = 'SELECT *
                     FROM %s
                     LIMIT %d, %d';

        $sql = sprintf(
            $sqlMarks,
            $tableName,
            $begin,
            $end
        );

        return static::$PDO->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Show the table description
     *
     * @param  string $tableName The table name
     * @return array             Array containing the result
     * @static
     */
    public static function descTable($tableName)
    {
        static::initialize();

        $sqlMarks = 'DESC %s';

        $sql = sprintf(
            $sqlMarks,
            $tableName
        );

        return static::$PDO->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /*-----  End of Public methods  ------*/

    /*=======================================
    =            Private methods            =
    =======================================*/

    /**
     * Utility method to reuse the same PDO instance at each call (work like a Singleton pattern)
     *
     * @param  string $dsn      The Data Source Name, or DSN, contains the information required to connect to the database
     * @param  string $username The user name for the DSN string. This parameter is optional for some PDO drivers
     * @param  string $password The password for the DSN string. This parameter is optional for some PDO drivers
     * @param  array  $options  A key => value array of driver-specific connection options
     * @static
     */
    private static function initialize($dsn = '', $username = '', $password = '', $options = array())
    {
        Ini::setIniFileName(static::INI_CONF_FILE);

        if (static::$printSQL === null) {
            // Load default printSQL value
            static::$printSQL = Ini::getParam('Console', 'printSql');
        }

        try {
            if (static::$PDO === null) {
                if ($username !== '' && $password !== '') {
                    if (count($options) > 0) {
                        static::$PDO = new \PDO($dsn, $username, $password, $options);
                    } else {
                        static::$PDO = new \PDO($dsn, $username, $password);
                    }
                } elseif ($dsn !== '') {
                    static::$PDO = new \PDO($dsn);
                } else {
                    // Load default database parameters
                    $params = Ini::getSectionParams('Database');

                    static::$PDO = new \PDO($params['dsn'], $params['username'], $params['password'], $params['options']);
                }

                // Load default PDO parameters
                $params = Ini::getSectionParams('PDO');

                foreach ($params as $paramName => $paramValue) {
                    static::$PDO->setAttribute(constant('\PDO::' . $paramName), $paramValue);
                }

                static::setPDOStatement();
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), Exception::$CRITICAL);
        }
    }

    /**
     * Set the PDOStatement custom class
     */
    private static function setPDOStatement()
    {
        static::$PDO->setAttribute(
            \PDO::ATTR_STATEMENT_CLASS,
            array('\classes\PDOStatementCustom', array(static::$PDO, static::$printSQL))
        );
    }

    /*-----  End of Private methods  ------*/
}
