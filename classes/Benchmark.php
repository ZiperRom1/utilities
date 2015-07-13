<?php
/**
 * Bencharmark class to test methods performance
 *
 * @category Benchmark
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

namespace utilities\classes;

use \utilities\classes\exception\ExceptionManager as Exception;

/**
 * Benchmark methods by symply put them in an array sent to the constructors
 *
 * @class Benchmark
 * @todo complete the class
 */
class Benchmark
{
    /**
     * @var callable[] $functionsArray Array containing methods to test (min 2)
     */
    private $functionsArray = array();
    /**
     * @var float $t1 Initial timestamp
     */
    private $t1;
    /**
     * @var float $t2 Terminal timestamp
     */
    private $t2;

    /*=====================================
    =            Magic methods            =
    =====================================*/
    
    /**
     * Constructor that takes an array of methods as first parameter and array of params as second parameter
     *
     * @param callable[] $functions Methods to test
     * @param array      $params    Methods parameters
     */
    public function __construct($functions = null, $params = null)
    {
        if ($functions === null) {
            throw new Exception("ERROR::There is no parameter", Exception::$PARAMETER);
        }

        if (!is_array($functions)) {
            throw new Exception("ERROR::Parameter 1 must an array of functions", Exception::$PARAMETER);
        }

        if (count($functions) < 2) {
            throw new Exception("ERROR::Array must contain at least 2 functions", Exception::$PARAMETER);
        }

        foreach ($functions as $function) {
            if (!is_callable($function)) {
                throw new Exception("ERROR::Array values must be functions", Exception::$PARAMETER);
            }
        }
    }
    
    /*-----  End of Magic methods  ------*/

    /*======================================
    =            Public methods            =
    ======================================*/
    
    /**
     * Run the benchmark by defining a number of iteration
     *
     * @param  integer $iterations The number of iteration to process
     */
    public function runByIteration($iterations)
    {
        for ($i = 0; $i < $iterations; $i++) {
            # code...
        }
    }
    
    /*-----  End of Public methods  ------*/
}
