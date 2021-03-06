<?php
/**
 * Trait to set beautifull indent on multiple array values
 *
 * @category Trait
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

namespace traits;

use \classes\ExceptionManager as Exception;

/**
 * Utility methods to smart align values
 *
 * @trait PrettyOutputTrait
 */
trait PrettyOutputTrait
{
    /**
     * @var array $beautifullIndentMaxSize Array containing the max size of each array
     */
    public static $beautifullIndentMaxSize = array();

    /*======================================
    =            Public methods            =
    ======================================*/

    /**
     * Return the value with the exact number of right extra spaces to keep all the values align
     *
     * @param  string              $value      The value to print
     * @param  string[]|array[]    $arrays     The array of values to align the value with (can be an array of array)
     * @param  integer             $extraSize  An extra size to add to the max value size of the category
     * @param  integer             $position   The position to align as str_pad constant DEFAULT STR_PAD_RIGHT
     * @return string                          The formatted value with extra spaces
     */
    public function smartAlign($value, $arrays, $extraSize = 0, $position = STR_PAD_RIGHT)
    {
        // if The array passed is a simple strings array we transform it into an array of one strings array
        if (!is_array($arrays[0])) {
            $tempArray = $arrays;
            $arrays = array($tempArray);
        }

        $max = 0;

        foreach ($arrays as $array) {
            $arrayHash = $this->md5Array($array);

            if (!isset(static::$beautifullIndentMaxSize[$arrayHash])) {
                $this->setMaxSize($array, 0, $arrayHash);
            }

            $max += static::$beautifullIndentMaxSize[$arrayHash];
        }

        return str_pad($value, $max + $extraSize, ' ', $position);
    }

    /**
     * Format an array in a pretty indented output string
     *
     * @param  array   $array The array to format
     * @param  integer $depth OPTIONAL the array values depth DEFAULT 1
     * @return string         The array as a pretty string
     */
    public function prettyArray($array, $depth = 1)
    {
        $arrayFormatted = array();
        $arrayIndent    = implode(array_fill(0, $depth - 1, "\t"));
        $valuesIndent   = implode(array_fill(0, $depth, "\t"));
        $keys           = array_keys($array);

        foreach ($array as $key => $value) {
            $alignKey = $valuesIndent . $this->smartAlign($key, $keys) . ' => ';

            if (is_array($value)) {
                $arrayFormatted[] = $alignKey . $this->prettyArray($value, $depth + 1);
            } else {
                $arrayFormatted[] = $alignKey . $this->formatVariable($value);
            }
        }

        return 'array(' . PHP_EOL . implode(',' . PHP_EOL, $arrayFormatted) . PHP_EOL . $arrayIndent . ')';
    }

    /**
     * Return the variable in a formatted string with type and value
     *
     * @param  mixed   $variable The variable (can be any type)
     * @param  integer $depth    OPTIONAL the array values depth for the prettyArray method DEFAULT 1
     * @return string            The variable in a formatted string
     */
    public function formatVariable($variable, $depth = 1)
    {
        switch (gettype($variable)) {
            case 'array':
                $argumentFormatted = $this->prettyArray($variable, $depth);
                break;

            case 'object':
                $argumentFormatted = 'object::' . get_class($variable);
                break;

            case 'resource':
                $argumentFormatted = 'resource::' . get_resource_type($variable);
                break;

            case 'boolean':
                $argumentFormatted = $variable ? 'true' : 'false';
                break;

            case 'integer':
                $argumentFormatted = (int) $variable;
                break;

            case 'string':
                $argumentFormatted = '"' . $variable . '"';
                break;

            case 'NULL':
                $argumentFormatted = 'null';
                break;

            default:
                $argumentFormatted = $variable;
                break;
        }

        return $argumentFormatted;
    }

    /**
     * Get the max size of a array
     *
     * @param  string $array The array
     * @return integer       The max size
     */
    public function getMaxSize($array)
    {
        $arrayHash = $this->md5Array($array);

        if (!isset(static::$beautifullIndentMaxSize[$arrayHash])) {
            $this->setMaxSize($array, 0, $arrayHash);
        }

        return static::$beautifullIndentMaxSize[$arrayHash];
    }

    /**
     * Get the md5 hash of an array
     *
     * @param  array   $array The array to hash
     * @param  boolean $sort  If the array should be sorted before hashing DEFAULT true
     * @return string        The md5 hash
     */
    public function md5Array($array, $sort = true)
    {
        if ($sort) {
            array_multisort($array);
        }

        return md5(json_encode($array));
    }

    /*-----  End of Public methods  ------*/

    /*=======================================
    =            Private methods            =
    =======================================*/

    /**
     * Process the max value size of a category
     *
     * If the category is processed and the array didn't change, the category is not reprocessed
     *
     * @param string[]   $strings   The array to calculate max size of
     * @param integer    $minSize   OPTIONAL The minium size DEFAULT 0
     * @param string     $arrayHash OPTIONAL The already calculated array hash DEFAULT null
     */
    private function setMaxSize($strings = array(), $minSize = 0, $arrayHash = null)
    {
        if ($arrayHash === null) {
            $arrayHash = $this->md5Array($strings);
        }

        $max = 0;

        foreach ($strings as $string) {
            $stringSize = strlen((string) $string);

            if ($stringSize > $max) {
                $max = $stringSize;
            }
        }

        static::$beautifullIndentMaxSize[$arrayHash] = max($max, $minSize);
    }

    /*-----  End of Private methods  ------*/
}
