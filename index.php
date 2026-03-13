<?php
/**
 * JiFramework — Entry Point
 *
 * Plug-and-play mode  (router_enabled = false):
 *   Include this file or any page directly. $app is available immediately.
 *
 * Router mode (router_enabled = true):
 *   All requests route through this file via .htaccess.
 *   Define your routes below, then call $app->router->dispatch().
 */

require_once __DIR__ . '/vendor/autoload.php';

use JiFramework\Core\App\App;
use JiFramework\Config\Config;

$app = new App();

// =============================================================================
// ROUTER MODE  (Config::$routerEnabled = true)
// =============================================================================
if (Config::$routerEnabled) {

    // --- File-based routes ---------------------------------------------------
    // $app is automatically available inside every page file.
    // URL params are extracted as variables (e.g. {id} becomes $id).

    $app->router->get('/', 'pages/home.php');
    $app->router->get('/about', 'pages/about.php');

    // URL param example — inside users.php you get $id directly
    $app->router->get('/users/{id}', 'pages/users.php');

    // --- Closure-based routes ------------------------------------------------
    // Good for quick responses (health checks, simple JSON endpoints, redirects).

    $app->router->get('/ping', function () {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
    });

    // Closure with URL param
    $app->router->get('/hello/{name}', function ($name) {
        echo 'Hello, ' . htmlspecialchars($name) . '!';
    });

    // Multiple methods on one route
    $app->router->match(['GET', 'POST'], '/contact', 'pages/contact.php');

    // POST route
    $app->router->post('/login', 'pages/auth/login.php');

    // PUT / DELETE via hidden _method field in HTML forms
    $app->router->put('/users/{id}', 'pages/users/update.php');
    $app->router->delete('/users/{id}', 'pages/users/delete.php');

    // Run the router — matches the current request and calls the handler
    $app->router->dispatch();

// =============================================================================
// PLUG-AND-PLAY MODE  (Config::$routerEnabled = false)
// =============================================================================
} else {
    // In plug-and-play mode this file is not used as a router.
    // Each PHP page includes vendor/autoload.php, calls Config::initialize(),
    // creates new App(), and uses $app directly.
    //
    // Example page (pages/home.php):
    //
    //   require_once '../vendor/autoload.php';
    //   use JiFramework\Core\App\App;
    //   use JiFramework\Config\Config;
    //   Config::initialize();
    //   $app = new App();
    //
    //   $users = $app->db->table('users')->get();
    //   $app->logger->info('Home page loaded');

    echo 'JiFramework is ready. Set router_enabled = true in jiconfig.php to use router mode.';
}
