# App Component

The App component is the central container for the JiFramework, managing dependencies, configuration, and providing access to all other components. It serves as a simple dependency injection container that initializes and provides access to framework services.

## Basic Usage

```php
// Create a new application instance
$app = new \JiFramework\Core\App\App();

// Access components through the app container
$db = $app->db;
$auth = $app->auth;
$logger = $app->logger;
```

## Available Components

The App container provides access to the following components:

| Component | Property | Description |
|-----------|----------|-------------|
| Database | `$app->db` | QueryBuilder for database operations |
| Auth | `$app->auth` | User authentication and authorization |
| Logger | `$app->logger` | Application logging service |
| Session | `$app->sessionManager` | Session management |
| Cache | `$app->cache` | Data caching service |
| Error Handler | `$app->errorHandler` | Error and exception handling |
| File Manager | `$app->fileManager` | File operations |
| Validator | `$app->validation` | Data validation |
| Encryption | `$app->encryption` | Data encryption and security |
| Rate Limiter | `$app->rateLimiter` | Request rate limiting |

## Methods

### redirect($url, $statusCode = 302)

Redirects the user to another URL.

```php
$app->redirect('/dashboard');
```

### exit($message = '', $statusCode = 0)

Terminates the application with an optional message and status code.

```php
$app->exit('Maintenance mode active', 503);
```

### setConfig($key, $value)

Sets a configuration value.

```php
$app->setConfig('debug', true);
```

### getConfig($key, $default = null)

Gets a configuration value with an optional default.

```php
$debugMode = $app->getConfig('debug', false);
```

## Custom Service Registration

You can register your own services with the App container:

```php
$app->register('myService', function($app) {
    return new MyCustomService($app->db);
});

// Later, use the service
$service = $app->myService;
```

## Advanced Usage

### Using App in a Singleton Pattern

```php
// In a bootstrap file
$app = new \JiFramework\Core\App\App();
\JiFramework\Core\App\App::setInstance($app);

// In another file
$app = \JiFramework\Core\App\App::getInstance();
$db = $app->db;
```

### Custom Configuration

```php
// Create with custom configuration
$config = [
    'debug' => true,
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'myapp',
        'username' => 'root',
        'password' => ''
    ]
];

$app = new \JiFramework\Core\App\App($config);
``` 