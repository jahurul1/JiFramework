<?php
namespace JiFramework\Core\App;

use JiFramework\Config\Config;
use JiFramework\Core\Database\QueryBuilder;
use JiFramework\Core\Auth\Auth;
use JiFramework\Core\Session\SessionManager;
use JiFramework\Core\Utilities\DateTimeHelper;
use JiFramework\Core\Utilities\FileManager;
use JiFramework\Core\Utilities\Str;
use JiFramework\Core\Utilities\Paginator;
use JiFramework\Core\Utilities\Url;
use JiFramework\Core\Network\HttpClient;
use JiFramework\Core\Utilities\Request;
use JiFramework\Core\Security\Encryption;
use JiFramework\Core\Cache\CacheManager;
use JiFramework\Core\Utilities\ExecutionTimer;
use JiFramework\Core\Security\RateLimiter;
use JiFramework\Core\Language\LanguageManager;
use JiFramework\Core\Error\ErrorPageHandler;
use JiFramework\Core\Logger\Logger;
use JiFramework\Core\Error\ErrorHandler;
use JiFramework\Core\Security\AccessControl;
use JiFramework\Exceptions\HttpException;
use JiFramework\Core\Utilities\Validator;
use JiFramework\Core\Http\Router;

/**
 * @property QueryBuilder      $db
 * @property Auth              $auth
 * @property SessionManager    $sessionManager
 * @property DateTimeHelper    $dateTime
 * @property FileManager       $fileManager
 * @property Str               $str
 * @property Paginator         $paginator
 * @property Url               $url
 * @property HttpClient        $http
 * @property Request           $request
 * @property Encryption        $encryption
 * @property \JiFramework\Core\Cache\CacheInterface $cache
 * @property ExecutionTimer    $executionTimer
 * @property LanguageManager   $language
 * @property Logger            $logger
 * @property Validator         $validator  make(), passes(), fails(), errors(), first(), throw(), check(), checkOrFail()
 * @property Router            $router
 * @property RateLimiter       $rateLimiter
 * @property AccessControl     $accessControl
 * @property ErrorHandler      $errorHandler
 * @property ErrorPageHandler  $errorPageHandler
 *
 * @method void abort(int $statusCode, string $message = '')
 */
class App
{
    /** Singleton instance — allows Model classes to access $app from anywhere. */
    private static ?App $instance = null;

    /** Default database connection name. */
    private string $connectionName;

    /** Kept as a real property — used internally during construction. */
    private ErrorPageHandler $errorPageHandler;

    /** Resolved (instantiated) component instances, keyed by name. */
    private array $resolved = [];

    /** Factory closures — each component is instantiated only on first access. */
    private array $registry = [];

    /**
     * Return the current App instance.
     * Used by Model to access the database without passing $app around.
     */
    public static function getInstance(): static
    {
        return self::$instance;
    }

    // =========================================================================
    // Bootstrap
    // =========================================================================

    public function __construct(string $connectionName = 'primary')
    {
        Config::initialize();

        self::$instance       = $this;
        $this->connectionName = $connectionName;

        // -- Always eager: error infrastructure --
        $this->errorPageHandler             = ErrorPageHandler::getInstance();
        $this->resolved['errorPageHandler'] = $this->errorPageHandler;

        // Single Logger instance shared by ErrorHandler and $app->logger
        // so PHP errors and application logs all go to the same file
        $this->resolved['logger'] = new Logger();
        foreach (Config::$warnings as $warning) {
            $this->resolved['logger']->warning($warning);
        }

        $this->resolved['errorHandler'] = new ErrorHandler($this->resolved['logger'], $this->errorPageHandler);
        $this->resolved['errorHandler']->register();

        // -- Always eager: security gates --
        // Environment resolved now so RateLimiter can receive it.
        $this->resolved['request']     = new Request();
        $this->resolved['rateLimiter'] = new RateLimiter($this->resolved['request']);
        $this->resolved['rateLimiter']->enforceRateLimit();

        $this->resolved['accessControl'] = new AccessControl($this->resolved['request']);
        if (!$this->resolved['accessControl']->isAccessAllowed()) {
            throw new HttpException(403, 'Access Denied');
        }

        // -- Register lazy factories for everything else --
        $this->buildRegistry();

        // -- Auto-load model files from project's models/ directory --
        $modelsDir = Config::$basePath . 'models/';
        if (is_dir($modelsDir)) {
            foreach (glob($modelsDir . '*.php') as $file) {
                require_once $file;
            }
        }
    }

    // =========================================================================
    // Lazy loading — magic methods
    // =========================================================================

    /**
     * Property-style access: $app->logger, $app->db, etc.
     * Components are instantiated on first access and cached.
     */
    public function __get(string $name)
    {
        // db has its own resolver to support named connections
        if ($name === 'db') {
            return $this->resolveDb($this->connectionName);
        }

        // Return cached instance if already resolved
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        // Resolve from registry and cache
        if (isset($this->registry[$name])) {
            $this->resolved[$name] = ($this->registry[$name])();
            return $this->resolved[$name];
        }

        return null;
    }

    /**
     * Method-style access for named database connections.
     *
     * $app->db('secondary')   — returns QueryBuilder for 'secondary'
     * $app->db('primary')     — returns same instance as $app->db
     */
    public function __call(string $name, array $args)
    {
        if ($name === 'db') {
            return $this->resolveDb($args[0] ?? $this->connectionName);
        }
        return null;
    }

    /**
     * isset($app->logger) returns true even before the component is resolved.
     */
    public function __isset(string $name): bool
    {
        if ($name === 'db') {
            return true;
        }
        return isset($this->resolved[$name]) || isset($this->registry[$name]);
    }

    // =========================================================================
    // Internal resolvers
    // =========================================================================

    /**
     * Resolve and cache a QueryBuilder for the given connection name.
     * Each connection name gets its own slot in $resolved.
     */
    private function resolveDb(string $connectionName): QueryBuilder
    {
        $key = 'db.' . $connectionName;
        if (!isset($this->resolved[$key])) {
            $this->resolved[$key] = new QueryBuilder($connectionName);
        }
        return $this->resolved[$key];
    }

    /**
     * Register factory closures for all lazy-loaded components.
     * Nothing here is instantiated until first accessed via $app->name.
     */
    private function buildRegistry(): void
    {
        $this->registry = [
            'auth'          => fn() => new Auth(),
            'sessionManager'=> fn() => new SessionManager(),
            'dateTime'      => fn() => new DateTimeHelper(),
            'fileManager'   => fn() => new FileManager(),
            'str'           => fn() => new Str(),
            'paginator'     => fn() => new Paginator(),
            'url'           => fn() => new Url(),
            'http'          => fn() => new HttpClient(),
            'encryption'    => fn() => new Encryption(),
            'cache'         => fn() => CacheManager::getInstance(),
            'executionTimer'=> fn() => new ExecutionTimer(),
            'language'      => fn() => Config::$multiLang ? new LanguageManager() : null,
            'router'        => fn() => Config::$routerEnabled ? new Router($this) : null,
            'validator'     => fn() => new Validator(),
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function redirect(string $url): void
    {
        header("Location: $url");
        exit();
    }

    public function exit(int $statusCode = 200, string $msg = ''): void
    {
        if ($statusCode >= 100 && $statusCode <= 599) {
            http_response_code($statusCode);
        } else {
            http_response_code(500);
            $msg = 'Invalid status code provided';
        }
        echo $msg;
        exit;
    }

    /**
     * Send a JSON response and terminate the script.
     *
     * Sets the Content-Type header to application/json, encodes the data array,
     * and exits. Use this for all API and AJAX responses.
     *
     * @param int   $statusCode HTTP status code (100-599).
     * @param array $data       Data to JSON-encode and send as the response body.
     */
    public function json(int $statusCode, array $data): void
    {
        if ($statusCode >= 100 && $statusCode <= 599) {
            http_response_code($statusCode);
        } else {
            http_response_code(500);
            $data = ['error' => 'Invalid status code provided'];
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Abort the request by throwing an HttpException.
     *
     * The registered ErrorHandler catches it, logs it, and renders the appropriate
     * error page (HTML or JSON) based on the request type and app mode.
     *
     * @param int    $statusCode HTTP status code (e.g. 404, 403, 401, 500).
     * @param string $message    Optional message. In production mode only the
     *                           generic status text is shown to the user; the
     *                           message is always written to the log.
     * @throws HttpException always.
     */
    public function abort(int $statusCode, string $message = ''): void
    {
        throw new HttpException($statusCode, $message);
    }
}
