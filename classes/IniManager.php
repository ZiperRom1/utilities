<?php
/**
 * Ini file manager to get / set parameters with comments handle and pretty align
 *
 * @category INI
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

namespace classes;

use \classes\ExceptionManager as Exception;
use \classes\LoggerManager as Logger;

/**
 * Helper class to provide access read and write to an ini conf file.
 * Can set and get any parameter in the given ini file.
 *
 * @class IniManager
 * @todo make this class non static
 */
class IniManager
{
    /**
     * ini file path (can be only the file name if the path is under the include_path of php ini conf)
     */
    private static $INI_FILE_NAME = 'conf.ini';

    /**
     * @var array $iniValues The INI params name => value
     */
    private static $iniValues;
    /**
     * @var array $iniSectionsComments The INI section comments
     */
    private static $iniSectionsComments;
    /**
     * @var array $iniSectionsComments The INI params comments
     */
    private static $iniParamsComments;
    /**
     * @var boolean $initialized True if the INI file has been parsed else false
     */
    private static $initialized = false;

    /*=====================================
    =            Magic methods            =
    =====================================*/

    /**
     * constructor nevers called (see initialize below)
     */
    public function __construct()
    {
    }

    /**
     * One time call constructor to get ini values
     */
    private static function initialize()
    {
        /* To use constants in INI conf file */
        Logger::globalConstDefine();

        if (!static::$initialized) {
            static::$iniValues           = parse_ini_file(static::$INI_FILE_NAME, true);
            static::$iniSectionsComments = static::parseSectionsComments();
            static::$iniParamsComments   = static::parseParamsComments();
            static::$initialized         = true;
        }

        if (static::$iniValues === false) {
            throw new Exception(
                'ERROR::The INI file ' . static::$INI_FILE_NAME . ' cannot be found',
                Exception::$WARNING
            );
        }
    }

    /*-----  End of Magic methods  ------*/

    /*======================================
    =            Public methods            =
    ======================================*/

    /**
     * Set the ini file name
     *
     * @param string $iniFileName The ini file name string
     */
    public static function setIniFileName($iniFileName)
    {
        static::$INI_FILE_NAME = $iniFileName;
        static::$initialized   = false;

        static::initialize();
    }

    /**
     * Get the param value of the specified section
     *
     * @param  string    $section The section name
     * @param  string    $param   The param name
     * @throws Exception          If the param name is not a string
     * @throws Exception          If the param doesn't exist in the specified section
     * @return mixed              The param value (can be any type)
     */
    public static function getParam($section = null, $param = null)
    {
        static::initialize();

        $params = static::getSectionParams($section);

        if (is_string($param)) {
            if (static::paramExists($section, $param)) {
                return $params[$param];
            } else {
                throw new Exception(
                    'ERROR::The section ' . $section . ' doesn\'t contain the parameter ' . $param,
                    Exception::$WARNING
                );
            }
        } else {
            throw new Exception('ERROR::Second parameter must be a String (the param name)', Exception::$PARAMETER);
        }
    }

    /**
     * Get all the parameters of the specified section
     *
     * @param  string       $section The section name
     * @throws Exception             If each final section type is not a string
     * @throws Exception             If any section doesn't exist in the ini file
     * @return array                 An array containing all the params values
     */
    public static function getSectionParams($section = null)
    {
        static::initialize();
        $return = array();

        if (is_array($section)) {
            foreach ($section as $sectionLevel) {
                if (is_string($sectionLevel)) {
                    if (static::sectionExists($section)) {
                        $return[$sectionLevel] = static::$iniValues[$sectionLevel];
                    } else {
                        throw new Exception(
                            'ERROR::The section ' . $sectionLevel . ' doesn\'t exist in the ini conf file',
                            Exception::$WARNING
                        );
                    }
                } else {
                    throw new Exception(
                        'ERROR::Parameter section must be a String or an array of String',
                        Exception::$PARAMETER
                    );
                }
            }
        } elseif (is_string($section)) {
            if (static::sectionExists($section)) {
                $return[$section] = static::$iniValues[$section];
            } else {
                echo 'ERROR::The section ' . $section . ' doesn\'t exist in the ini conf file' . PHP_EOL;
                throw new Exception(
                    'ERROR::The section ' . $section . ' doesn\'t exist in the ini conf file',
                    Exception::$WARNING
                );
            }

            $return = static::$iniValues[$section];
        } else {
            throw new Exception(
                'ERROR::Parameter section must be a String or an array of String',
                Exception::$PARAMETER
            );
        }

        return $return;
    }

    /**
     * Get all parmameters of the ini file
     *
     * @return array Multi dimensional array
     */
    public static function getAllParams()
    {
        static::initialize();

        return static::$iniValues;
    }

    /**
     * Get all the parameters comment
     *
     * @return array Multi dimensional array
     */
    public static function getParamsComment()
    {
        static::initialize();

        return static::$iniParamsComments;
    }

    /**
     * Get all the sections first level name
     *
     * @return array One dimensional array
     */
    public static function getSections()
    {
        static::initialize();

        return array_keys(static::$iniValues);
    }

    /**
     * Get all the setions comment
     *
     * @return array One dimensional array
     */
    public static function getSectionsComment()
    {
        static::initialize();

        return static::$iniSectionsComments;
    }

    /**
     * Set a parameter in the ini file
     *
     * @param string $section The section name
     * @param string $param   The param name
     * @param mixed  $value   The param value (can be any type except multi dimensional array)
     */
    public static function setParam($section = null, $param = null, $value = null)
    {
        static::initialize();
        static::addParam($section, $param, $value);
        static::$initialized = false;
    }

    /**
     * Set a section comment in the ini file
     *
     * @param  string    $section The section name
     * @param  string    $comment The comment string
     * @throws exception          If section does not exist
     */
    public static function setSectionComment($section = null, $comment = '')
    {
        static::initialize();

        if (!static::sectionExists($section)) {
            throw new Exception('The section ' . $section . 'does not exist', Exception::$WARNING);
        }

        static::addCommentToSection($section, $comment);
        static::$initialized = false;
    }

    /**
     * Set a param comment in the ini file
     *
     * @param  string    $section The section name
     * @param  string    $param   The param name
     * @param  string    $comment The comment string
     * @throws exception          If section does not exist
     * @throws exception          If param does not exist for the section
     */
    public static function setParamComment($section = null, $param = null, $comment = '')
    {
        static::initialize();

        if (!static::sectionExists($section)) {
            throw new Exception('The section ' . $section . 'does not exist', Exception::$WARNING);
        }

        if (!static::paramExists($section, $param)) {
            throw new Exception(
                'The parameter ' . $param . 'does not exist in the section ' . $section,
                Exception::$WARNING
            );
        }

        static::addCommentToParam($section, $param, $comment);
        static::$initialized = false;
    }

    /*-----  End of Public methods  ------*/

    /*=======================================
    =            Private methods            =
    =======================================*/

    /**
     * Check if the section exists in teh ini file
     *
     * @param  string  $section The section name
     * @return boolean          Section exists
     */
    private static function sectionExists($section)
    {
        return array_key_exists($section, static::$iniValues);
    }

    /**
     * Check if the parameter exists in the specified section
     *
     * @param  string  $section The section name
     * @param  string  $param   The parameter name
     * @return boolean          Parameter exists
     */
    private static function paramExists($section, $param)
    {
        if (preg_match('/^(?P<subsection>.*)\[(?P<key>.*)\]$/', $param, $matches) === 1) {
            $exist = array_key_exists($matches['key'], static::$iniValues[$section][$matches['subsection']]);
        } else {
            $exist = array_key_exists($param, static::$iniValues[$section]);
        }

        return $exist;
    }

    /**
     * Helper to add or set a param in the ini file
     *
     * @param string $section The section name
     * @param string $param   The param name
     * @param mixed  $value   The param value (can be any type except mutli dimensional array)
     */
    private static function addParam($section, $param, $value)
    {
        static::initialize();
        static::$iniValues[$section][$param] = $value;

        file_put_contents(
            static::$INI_FILE_NAME,
            static::arrayToIni(),
            FILE_USE_INCLUDE_PATH | LOCK_EX
        );
    }

    /**
     * Helper to add or set a comment to the section in the ini file
     *
     * @param string $section The section name
     * @param string $comment The comment string
     */
    private static function addCommentToSection($section, $comment)
    {
        static::initialize();
        static::$iniSectionsComments[$section] = $comment;

        file_put_contents(
            static::$INI_FILE_NAME,
            static::arrayToIni(),
            FILE_USE_INCLUDE_PATH | LOCK_EX
        );
    }

    /**
     * Helper to add or set a comment to he param in the ini file
     *
     * @param string $section The section name
     * @param string $param   The parameter name
     * @param string $comment The comment string
     */
    private static function addCommentToParam($section, $param, $comment)
    {
        static::initialize();
        static::$iniParamsComments[$section][$param] = $comment;

        file_put_contents(
            static::$INI_FILE_NAME,
            static::arrayToIni(),
            FILE_USE_INCLUDE_PATH | LOCK_EX
        );
    }

    /**
     * Helper format a commentary
     *
     * @param  string $comment The litteral comment (can contain line break PHP_EOL)
     * @return string          The formated comment (each line start with a ; followed by a space)
     */
    private static function formatComment($comment)
    {
        return '; ' . str_replace(PHP_EOL, PHP_EOL . "; ", trim($comment));
    }

    /**
     * Get the max characters length parameters name
     *
     * @param  array   $attributes   Parameters as associativ array
     * @return integer               The max length parameter name
     */
    private static function getMaxLength($attributes)
    {
        $max    = 0;
        $length = 0;

        foreach ($attributes as $name => $value) {
            $length = strlen($name);

            if ($length > $max) {
                $max = $length;
            }
        }

        return $max;
    }

    /**
     * Return the spaces needed to align the value with others
     *
     * @param  string  $attribute Parameter name
     * @param  integer $maxLength The max length parameter name
     * @return string             The spaces needed to align the value
     */
    private static function alignValues($attribute, $maxLength)
    {
        $spaces = array_fill(0, $maxLength - strlen($attribute), ' ');

        return implode($spaces);
    }

    /**
     * Helper to parse an ini array conf to an ini string (ini file content)
     *
     * @return string The ini file content
     */
    private static function arrayToIni()
    {
        $iniString = '';

        foreach (static::$iniValues as $section => $sectionValue) {
            if (isset(static::$iniSectionsComments[$section])) {
                $iniString .= static::formatComment(static::$iniSectionsComments[$section]) . PHP_EOL;
            }

            $iniString .= "[" . $section . "]" . PHP_EOL;
            $maxLength = static::getMaxLength($sectionValue);

            foreach ($sectionValue as $param => $value) {
                if (is_array($value)) {
                    $maxLength = static::getMaxLength($value);

                    foreach ($value as $subSectionLevel => $subSectionValue) {
                        $iniString .= $param
                            . "["
                            . $subSectionLevel
                            ."]"
                            . static::alignValues($subSectionLevel, $maxLength)
                            . " = "
                            . (is_numeric($subSectionValue) ? $subSectionValue : '"' . $subSectionValue . '"');

                        if (isset(static::$iniParamsComments[$section][$param . '[' . $subSectionLevel . ']'])) {
                            $iniString .= ' '
                            . static::formatComment(
                                static::$iniParamsComments[$section][$param . '[' . $subSectionLevel . ']']
                            );
                        }

                        $iniString .= PHP_EOL;
                    }
                } else {
                    $iniString .= $param
                        . static::alignValues($param, $maxLength)
                        ." = "
                        . (is_numeric($value) ? $value : '"' . $value . '"');

                    if (isset(static::$iniParamsComments[$section][$param])) {
                        $iniString .= ' ' . static::formatComment(static::$iniParamsComments[$section][$param]);
                    }

                    $iniString .= PHP_EOL;
                }
            }

            $iniString .= PHP_EOL;
        }

        return trim($iniString);
    }

    /**
     * Helper to parse an ini conf file and get all the sections comments into an array
     *
     * @return array One dimensional array containg all the sections comments
     */
    private static function parseSectionsComments()
    {
        $iniSectionsComments = array();

        preg_match_all(
            '/(?P<comment>(;.*\R)+)\[(?P<section>[A-Za-z0-9_ ]*)\]/',
            file_get_contents(static::$INI_FILE_NAME, true),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $iniSectionsComments[$match['section']] = trim(str_replace('; ', '', $match['comment']));
        }

        return $iniSectionsComments;
    }

    /**
     * Helper to parse an ini conf file and get all the params comments into an array
     *
     * @return array Multi dimensional array containg all the params comments
     * @todo debug
     */
    private static function parseParamsComments()
    {
        $paramsComments = array();

        preg_match_all(
            '/\[(?P<name>[A-Za-z0-9_ ]*)\](?<content>\n.+)*/',
            file_get_contents(static::$INI_FILE_NAME, true),
            $sections
        );
        // var_dump($sections);
        // echo "\n.....................................\n";

        foreach ($sections as $key => $section) {
            // echo $key . "\n";
            // var_dump($section);
            // echo "\n.....................................\n";
            if (isset($section['name'])) {
                // var_dump($section['name']);
                preg_match_all(
                    '/(?P<param>.*) = .*; (?P<content>.*)/',
                    $section['content'],
                    $comments,
                    PREG_SET_ORDER
                );

                foreach ($comments as $comment) {
                    $paramsComments[$section['name']][trim($comment['param'])] = trim($comment['content']);
                }

                unset($comments);
            }
        }

        return $paramsComments;
    }

    /*-----  End of Private methods  ------*/
}
