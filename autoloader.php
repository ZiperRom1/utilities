<?php
/**
 * Autoloader function to not worry about including stuff anymore
 *
 * @category Autoloader
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

spl_autoload_register(function ($className) {
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
