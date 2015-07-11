<?php

use \utilities\classes\Console as Console;

include_once 'autoloader.php';

try {
    $console = new Console();
    $console->launchConsole();
} catch (Exception $e) {
} finally {
    exit(0);
}
