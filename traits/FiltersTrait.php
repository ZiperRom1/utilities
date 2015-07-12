<?php

namespace utilities\traits;

/**
 * Utility methods to filter arrays or strings
 *
 * @trait FiltersTrait
 */
trait FiltersTrait
{
    /**
     * Filter a matches array returned from a preg_match_all with named keys
     *
     * @param  array  $matches   The matches array returned by the preg_match_all function
     * @param  string $keyName   The key name of the future array (?P<keyName>expression)
     * @param  string $valueName The value name of the future array (?P<valueName>expression)
     * @return array             The filterd array with $keyName = $keyValue
     */
    public function filterPregMatchAllWithFlags($matches, $keyName, $valueName)
    {
        $cleanArray = array();

        foreach ($matches as $key => $values) {
            if (is_string($key) && $key === $keyName) {
                foreach ($values as $num => $value) {
                    $cleanArray[trim($value)] = trim($matches[$valueName][$num]);
                }
            }
        }

        return $cleanArray;
    }

    public function filterFecthAllByColumn($data)
    {
        $columnsArray = array();

        foreach ($data as $row) {
            foreach ($row as $columnName => $columnValue) {
                if (!isset($columnsArray[$columnName])) {
                    $columnsArray[$columnName] = array();
                }

                $columnsArray[$columnName][] = $columnValue;
            }
        }

        return $columnsArray;
    }
}
