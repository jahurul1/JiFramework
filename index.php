<?php
// Define the application root directory
define('APP_ROOT', dirname(__FILE__));

// Require the Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Initialize the app
$app = new JiFramework\Core\App\App();

// Your application code starts here
// ...

// Example route handling
$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    echo "Welcome to my JiFramework application!";
} else {
    echo "Page not found!";
}






