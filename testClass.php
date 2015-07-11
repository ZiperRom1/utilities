<?php

use \utilities\classes\entities\User as User;
use \utilities\classes\entitiesManager\UserEntityManager as EntityManager;
use \utilities\classes\entitiesCollection\UserCollection as Collection;
use \utilities\classes\DataBase as DB;
use \utilities\classes\exception\ExceptionManager as Exception;

include_once 'autoloader.php';

try {
    $entityManager = new EntityManager();
    $collection    = new Collection();

    for ($i = 0; $i < 10; $i++) {
        $user       = new User();
        $user->id   = $i;
        $user->name = 'User_' . $i;
        $collection->add($user);
        echo $user . PHP_EOL;
    }
    
    // $entityManager->setEntityCollection($collection);
    // $entityManager->saveCollection();
} catch (Exception $e) {
} finally {
    exit(0);
}
