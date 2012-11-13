<?php

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'), get_include_path())));

require_once '../library/Zend/Loader/AutoloaderFactory.php';
require_once '../library/Zend/Loader/ClassMapAutoloader.php';

Zend_Loader_AutoloaderFactory::factory(
        array(
            'Zend_Loader_ClassMapAutoloader' => array(
                __DIR__ . '/../library/autoload_classmap.php'
            ),
            'Zend_Loader_StandardAutoloader' => array(
                'prefixes' => array(
                    'Zend' => __DIR__ . '/../library/Zend'
                ),
                'fallback_autoloader' => true
            )
        )
);

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
$application->bootstrap()->run();
