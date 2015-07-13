<?php
/**
 * Console colors to output colors in console
 *
 * @category Console
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

namespace utilities\classes\logger;

/**
 * Class to get different color to output in a console
 *
 * @class ConsoleColors
 */
class ConsoleColors
{
    /**
     * @var array $foregroundColors Foreground colors
     */
    private $foregroundColors = array();
    /**
     * @var array $backgroundColors Background colors
     */
    private $backgroundColors = array();

    /*=====================================
    =            Magic methods            =
    =====================================*/
    
    /**
     * Constructor which sets up all the colors
     */
    public function __construct()
    {
        // Set up shell colors
        $this->foregroundColors['black'] = '0;30';
        $this->foregroundColors['dark_gray'] = '1;30';
        $this->foregroundColors['blue'] = '0;34';
        $this->foregroundColors['light_blue'] = '1;34';
        $this->foregroundColors['green'] = '0;32';
        $this->foregroundColors['light_green'] = '1;32';
        $this->foregroundColors['cyan'] = '0;36';
        $this->foregroundColors['light_cyan'] = '1;36';
        $this->foregroundColors['red'] = '0;31';
        $this->foregroundColors['light_red'] = '1;31';
        $this->foregroundColors['purple'] = '0;35';
        $this->foregroundColors['light_purple'] = '1;35';
        $this->foregroundColors['brown'] = '0;33';
        $this->foregroundColors['yellow'] = '1;33';
        $this->foregroundColors['light_gray'] = '0;37';
        $this->foregroundColors['white'] = '1;37';

        $this->backgroundColors['black'] = '40';
        $this->backgroundColors['red'] = '41';
        $this->backgroundColors['green'] = '42';
        $this->backgroundColors['yellow'] = '43';
        $this->backgroundColors['blue'] = '44';
        $this->backgroundColors['magenta'] = '45';
        $this->backgroundColors['cyan'] = '46';
        $this->backgroundColors['light_gray'] = '47';
    }
    
    /*-----  End of Magic methods  ------*/

    /*======================================
    =            Public methods            =
    ======================================*/
    
    /**
     * Return the string colored
     *
     * @param  string $string          The string to color
     * @param  string $foregroundColor The foreground color
     * @param  string $backgroundColor The background color
     * @return string                  The colored string
     */
    public function getColoredString($string, $foregroundColor = null, $backgroundColor = null)
    {
        $coloredString = '';

        // Check if given foreground color found
        if (isset($this->foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[" . $this->foregroundColors[$foregroundColor] . "m";
        }
        // Check if given background color found
        if (isset($this->backgroundColors[$backgroundColor])) {
            $coloredString .= "\033[" . $this->backgroundColors[$backgroundColor] . "m";
        }

        // Add string and end coloring
        $coloredString .=  $string . "\033[0m";

        return $coloredString;
    }

    /**
     * Returns all foreground color names
     *
     * @return string[] Foreground color names
     */
    public function getForegroundColors()
    {
        return array_keys($this->foregroundColors);
    }

    /**
     * Returns all background color names
     *
     * @return string[] Background color names
     */
    public function getBackgroundColors()
    {
        return array_keys($this->backgroundColors);
    }
    
    /*-----  End of Public methods  ------*/
}
