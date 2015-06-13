<?php

namespace utilities\classes\logger;

use \utilities\classes\logger\ConsoleLogger as ConsoleLogger;
use \utilities\classes\logger\FileLogger as FileLogger;

/**
* LoggerManager
*/
class LoggerManager
{
    /**
     * @notice if you add a Logger in const there, add it in globalConstDefine method aswell
     */
    const FILE    = 1;
    const CONSOLE = 2;

    private $implementedLoggers = array();

    public function __construct($loggerTypes = array(self::FILE))
    {
        if (is_array($loggerTypes)) {
            foreach ($loggerTypes as $loggerType) {
                $this->addLogger($loggerType);
            }
        }
    }

    /**
     * Logs avec un niveau arbitraire.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        foreach ($this->implementedLoggers as $loggerName => $logger) {
            $logger->log($level, $message, $context);
        }
    }

    /**
     * Add a logger to the implemented logger
     *
     * @param int $loggerType The logger type
     */
    public function addLogger($loggerType)
    {
        $loggerType = (int) $loggerType;

        if (!$this->hasLogger($loggerType)) {
            if ($loggerType === self::FILE) {
                $this->implementedLoggers[self::FILE] = new FileLogger();
            } elseif ($loggerType === self::CONSOLE) {
                $this->implementedLoggers[self::CONSOLE] = new ConsoleLogger();
            }
        }
    }
    
    /**
     * Remove a logger to the implemented logger
     *
     * @param  int $loggerType The logger type
     */
    public function removeLogger($loggerType)
    {
        if ($this->hasLogger($loggerType)) {
            unset($this->implementedLoggers[$loggerType]);
        }
    }

    /**
     * Add the const definition in a global scope to use it in an INI file
     */
    public static function globalConstDefine()
    {
        if (!defined('FILE_LOGGER')) {
            define('FILE_LOGGER', self::FILE);
        }

        if (!defined('CONSOLE_LOGGER')) {
            define('CONSOLE_LOGGER', self::CONSOLE);
        }
    }

    /**
     * Check if a logger is already implemented
     *
     * @param  int  $loggerType The logger type
     * @return boolean          Logger already implemented
     */
    private function hasLogger($loggerType)
    {
        return array_key_exists($loggerType, $this->implementedLoggers);
    }
}
