<?php

namespace utilities\classes\logger;

/**
 * Décrit les niveaux de journalisation
 */
class LogLevel
{
    const EMERGENCY = 0;
    const ALERT     = 1;
    const CRITICAL  = 2;
    const ERROR     = 3;
    const WARNING   = 4;
    const NOTICE    = 5;
    const INFO      = 6;
    const DEBUG     = 7;
    const PARAMETER = self::ERROR;
}