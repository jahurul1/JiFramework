<?php
namespace JiFramework\Core\App;

use JiFramework\Config\Config;
use JiFramework\Core\Database\QueryBuilder;
use JiFramework\Core\Auth\Auth;
use JiFramework\Core\Session\SessionManager;
use JiFramework\Core\Utilities\DateTimeHelper;
use JiFramework\Core\Utilities\FileManager;
use JiFramework\Core\Utilities\StringHelper;
use JiFramework\Core\Utilities\Pagination\PaginationHelper;
use JiFramework\Core\Network\UrlHelper;
use JiFramework\Core\Network\HttpRequestHelper;
use JiFramework\Core\Utilities\Environment\EnvironmentHelper;
use JiFramework\Core\Security\Encryption;
use JiFramework\Core\Cache\CacheManager;
use JiFramework\Core\Utilities\Performance\ExecutionTimer;
use JiFramework\Core\Security\RateLimiter;
use JiFramework\Core\Language\LanguageManager;
use JiFramework\Core\Error\ErrorPageHandler;
use JiFramework\Core\Logger\Logger;
use JiFramework\Core\Error\ErrorHandler;
use JiFramework\Core\Security\AccessControl;
use JiFramework\Core\Utilities\Validator;

class App
{
    
    public $db;

    /**
     * The name of the database connection.
     *
     * @var string
     */
    protected $connectionName;

    public $auth;

    public $sessionManager;

    public $dateTimeHelper;

    public $fileManager;

    public $stringHelper;

    public $pagination;

    public $url;

    public $httpRequest;

    public $environment;

    public $encryption;

    public $cache;

    public $executionTimer;

    public $rateLimiter;

    public $language;

    public $errorPageHandler;

    public $logger;

    public $errorHandler;

    public $accessControl;

    public $validator;


    public function __construct($connectionName = 'primary')
    {   
        // Initialize Session
        Config::initSession();

        $this->errorPageHandler = ErrorPageHandler::getInstance();

        // Initialize ErrorHandler by default................................................
        $this->errorHandler = new ErrorHandler(new Logger(Config::STORAGE_PATH . 'Logs/error_logs.log'), $this->errorPageHandler);
        // Register error and exception handlers
        $this->errorHandler->register();


        
        $this->connectionName = $connectionName;
        $this->db = new QueryBuilder($this->connectionName);
        $this->auth = new Auth();
        $this->sessionManager = new SessionManager();
        $this->dateTimeHelper = new DateTimeHelper();
        $this->fileManager = new FileManager();
        $this->stringHelper = new StringHelper();
        $this->pagination = new PaginationHelper();
        $this->url = new UrlHelper();
        $this->httpRequest = new HttpRequestHelper();
        $this->environment = new EnvironmentHelper();
        $this->encryption = new Encryption();
        $this->cache = CacheManager::getInstance();
        $this->executionTimer = new ExecutionTimer();
        if(Config::MULTI_LANG){
          $this->language = new LanguageManager();
        }
        
        $this->logger = new Logger();
        $this->validator = new Validator();
        
        // Initialize RateLimiter by default................................................
        $this->rateLimiter = new RateLimiter($this->environment);
        // Enforce rate limit
        $this->rateLimiter->enforceRateLimit();

        // Initialize AccessControl by default..............................................
        $this->accessControl = new AccessControl();
        // Check access
        if (!$this->accessControl->isAccessAllowed()) {
            // Deny access: you can redirect or display an error page
            $this->errorPageHandler->handle(403, 'Access Denied');
        }
    }

    /**
     * Redirect to a specified URL.
     *
     * @param string $url The URL to redirect to.
     * @return void
     */
    public function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    /**
     * Exits the script execution with the provided HTTP status code and message.
     * @param int    $statusCode The HTTP status code to send.
     * @param string $msg        The message to send in the response.
     */
    public function exit($statusCode = 200, $msg = '') {
		// Validate and set the HTTP status code
		if (is_numeric($statusCode) && ($statusCode >= 100) && ($statusCode <= 599)) {
			http_response_code($statusCode);
		} else {
			// Default to 500 Internal Server Error if an invalid status code is provided
			http_response_code(500);
			$msg = 'Invalid status code provided';
		}

		// Echo the JSON-encoded response
		echo $msg;

		// Terminate script execution
		exit;
	}
}


