<?php

namespace utilities\traits;

use \utilities\classes\exception\ExceptionManager as Exception;

/**
 * Utility methods to smart align values
 *
 * @trait BeautifullIndentTrait
 */
trait BeautifullIndentTrait
{
    /**
     * @var array $beautifullIndentMaxSize Array containing the max size of each category values
     */
    private static $beautifullIndentMaxSize = array();

    /**
     * Process the max value size of a category
     *
     * @param string  $category The category name
     * @param array   $strings  The category values
     * @param integer $minSize  The minium size DEFAULT 0
     */
    public function setMaxSize($category, $strings = array(), $minSize = 0)
    {
        if (!isset(self::$beautifullIndentMaxSize[$category])) {
            $max = 0;

            foreach ($strings as $string) {
                $stringSize = strlen((string) $string);

                if ($stringSize > $max) {
                    $max = $stringSize;
                }
            }

            self::$beautifullIndentMaxSize[$category] = max($max, $minSize);
        }
    }

    /**
     * Return the value with the exact number of right extra spaces to keep all the values align
     *
     * @param  string           $value     The value to print
     * @param  string|string[]  $category  The category (can be multiple if needed)
     * @param  integer          $extraSize An extra size to add to the max value size of the category
     * @param  integer          $position  The position to align as str_pad constant DEFAULT STR_PAD_RIGHT
     * @return string                      The formatted value with extra spaces
     */
    public function smartAlign($value, $category, $extraSize = 0, $position = STR_PAD_RIGHT)
    {
        if (is_array($category)) {
            $max = 0;

            foreach ($category as $categoryName) {
                $max += self::$beautifullIndentMaxSize[$categoryName];
            }
        } else {
            $max = self::$beautifullIndentMaxSize[$category];
        }

        return str_pad($value, $max + $extraSize, ' ', $position);
    }

    public function getMaxSize($category)
    {
        if (!isset(self::$beautifullIndentMaxSize[$category])) {
            throw new Exception('The category ' . $category . ' does not exist', Exception::$PARAMETER);
        }

        return self::$beautifullIndentMaxSize[$category];
    }

    public function getMaxSizeSumByCategory($categories)
    {
        $sum = 0;

        foreach ($categories as $category) {
            $sum += self::$beautifullIndentMaxSize[$category];
        }
        
        return $sum;
    }

    public function getMaxSizeSum()
    {
        return array_sum(self::$beautifullIndentMaxSize);
    }
}
