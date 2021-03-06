<?php
/**
 * Example of DataBase class used
 *
 * @category Example
 * @author   Romain Laneuville <romain.laneuville@hotmail.fr>
 */
use \classes\DataBase as DB;

include_once '\utilities\autoloader.php';

try {
    DB::beginTransaction();

    if (DB::exec('DELETE FROM table WHERE 1 = 1') > 1) {
        DB::rollBack();
    } else {
        DB::commit();
    }
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
} finally {
    exit(0);
}
