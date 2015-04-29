<?php

use \utilities\classes\Benchmark as Benchmark;
use \utilities\classes\logger\LoggerManager as Logger;
use \utilities\classes\exception\ExceptionManager as Exception;

spl_autoload_register(function($className) {
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require_once $fileName;
});

$func1 = function ($array) {
    $sum = 0;

    foreach ($array as $value) {
        $sum += $value;
    }

    return $sum;
};

$func2 = function ($array) {
    $sum = 0;

    return array_sum($array);
};

$functions = array($func1);

try {
    $logger    = new Logger(array(Logger::CONSOLE, Logger::FILE));
    $benchmark = new Benchmark($functions);

    $logger->log(Exception::$DEBUG, 'OK');
} catch (Exception $e) {
    $logger->log($e->getCode(), $e->getMessage(), $e->getTrace());
} finally {
    exit(0);
}
