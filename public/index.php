<?php


/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('UTC');
 
// Define path to application directory
defined('BASE_PATH') || define('BASE_PATH', realpath(__DIR__ . '/..'));
defined('PUBLIC_PATH') || define('PUBLIC_PATH', __DIR__);

chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';

//Error reporting for local server
if (getenv('APPLICATION_ENV') === 'development') {
    ini_set('display_errors', true);
    error_reporting(E_ALL);
}
elseif (getenv('APPLICATION_ENV') === 'production')
{
    ini_set('display_errors', false);
    error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
}

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
