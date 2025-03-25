<?php
// Define the application root directory
define('APP_ROOT', dirname(__DIR__, 2));

// Require the Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Initialize the app
$app = new JiFramework\Core\App\App();

// Example: Using the QueryBuilder
$db = $app->db;
$users = $db->table('users')->where('status', 'active')->get();

// Example: Using the Logger
$logger = $app->logger;
$logger->info('Application started');

// Example: Using the Session Manager
$session = $app->sessionManager;
$session->set('user_id', 1);

// Example: Using the Validator
$validator = new JiFramework\Core\Utilities\Validator();
$isValid = $validator->isEmail('user@example.com');

// Example: Using the Cache Manager
$cache = $app->cacheManager;
$cache->set('key', 'value', 3600);
$value = $cache->get('key');

// Example: Using the Encryption
$encryption = new JiFramework\Core\Security\Encryption();
$encrypted = $encryption->encrypt('secret message', $encryption->generateKey());
$decrypted = $encryption->decrypt($encrypted, $key);

// Example: Using the Rate Limiter
$rateLimiter = new JiFramework\Core\Security\RateLimiter();
$isAllowed = $rateLimiter->check('user_ip', 100, 3600);

// Example: Using the File Manager
$fileManager = new JiFramework\Core\Utilities\FileManager();
$fileManager->ensureDirectoryExists('uploads');

// Example: Using the String Helper
$stringHelper = new JiFramework\Core\Utilities\StringHelper();
$slug = $stringHelper->slugify('Hello World!');

// Example: Using the DateTime Helper
$dateTimeHelper = new JiFramework\Core\Utilities\DateTimeHelper();
$formattedDate = $dateTimeHelper->format('Y-m-d H:i:s');

// Example: Using the Pagination Helper
$paginationHelper = new JiFramework\Core\Utilities\Pagination\PaginationHelper();
$pagination = $paginationHelper->paginate($users, 10, 1);

// Example: Using the Environment Helper
$envHelper = new JiFramework\Core\Utilities\Environment\EnvironmentHelper();
$isProduction = $envHelper->isProduction();

// Example: Using the Execution Timer
$timer = new JiFramework\Core\Utilities\Performance\ExecutionTimer();
$timer->start();
// ... some code ...
$executionTime = $timer->stop();

// Example: Using the URL Helper
$urlHelper = new JiFramework\Core\Network\UrlHelper();
$baseUrl = $urlHelper->getBaseUrl();

// Example: Using the Access Control
$accessControl = new JiFramework\Core\Security\AccessControl();
$hasPermission = $accessControl->hasPermission('admin', 'edit_user');

// Output some results
echo "Welcome to JiFramework Example Application!\n";
echo "Database Query Result: " . count($users) . " users found\n";
echo "Cache Value: " . $value . "\n";
echo "Encrypted Message: " . $encrypted . "\n";
echo "Decrypted Message: " . $decrypted . "\n";
echo "Rate Limit Check: " . ($isAllowed ? "Allowed" : "Not Allowed") . "\n";
echo "Slug: " . $slug . "\n";
echo "Formatted Date: " . $formattedDate . "\n";
echo "Execution Time: " . $executionTime . " seconds\n"; 

