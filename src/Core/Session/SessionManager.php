<?php
namespace JiFramework\Core\Session;

use JiFramework\Config\Config;

class SessionManager
{
    /**
     * Session key for CSRF token.
     * 
     *  @var string
     */
    protected $csrfTokenKey;

    /**
     * Session key for flash messages.
     * 
     *  @var string
     */
    protected $flashMessageKey;

    /**
     * CSRF token expiration time in seconds.
     * 
     * @var int
     */
    protected $tokenExpiry;

    /**
     * Maximum number of CSRF tokens to store.
     *
     * @var int
     */
    protected $maxTokens;

    /**
     * Length of the CSRF token in bytes.
     *
     * @var int
     */
    protected $tokenLength;

    /**
     * Constructor initializes session keys and token settings.
     */
    public function __construct()
    {
        // Get keys from Config or set defaults
        $this->csrfTokenKey = Config::CSRF_TOKEN_KEY ?? '_csrf_tokens';
        $this->flashMessageKey = Config::FLASH_MESSAGE_KEY ?? '_flash_messages';

        // Get token settings from Config or set defaults
        $this->tokenExpiry = Config::CSRF_TOKEN_EXPIRY ?? 3600; // 1 hour
        $this->maxTokens = Config::CSRF_TOKEN_LIMIT ?? 100;
        $this->tokenLength = Config::CSRF_TOKEN_LENGTH ?? 32;
    }

    /**
     * Generate and store a CSRF token.
     *
     * @return string The generated CSRF token.
     */
    public function generateCsrfToken()
    {   
        // Generate a random token
        $token = bin2hex(random_bytes($this->tokenLength));

        // Get current timestamp
        $timestamp = time();
        
        // Check if the CSRF token session key is set and is an array
        if (!isset($_SESSION[$this->csrfTokenKey]) || !is_array($_SESSION[$this->csrfTokenKey])) {
            // If not, initialize it as an empty array
            $_SESSION[$this->csrfTokenKey] = [];
        }

        // If the number of stored tokens exceeds the maximum limit
        if (count($_SESSION[$this->csrfTokenKey]) >= $this->maxTokens) {
            // Sort tokens by timestamp in ascending order
            asort($_SESSION[$this->csrfTokenKey]);

            // Remove the oldest token
            array_shift($_SESSION[$this->csrfTokenKey]);
        }

        // Add the new token to the session
        $_SESSION[$this->csrfTokenKey][$token] = $timestamp;
        
        // Return the generated token
        return $token;  
    }

    /**
     * Verify the CSRF token.
     *
     * @param string $token The token to verify.
     * @return bool True if valid, false otherwise.
     */
    public function verifyCsrfToken($token){
        // Check if the token is in the session
        if (!$token) {
            // Check for token in headers (for AJAX requests)
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        
        // Current timestamp
        $currentTime  = time();

        // Check if the token is in the session
        if (isset($_SESSION[$this->csrfTokenKey][$token])) {
            $tokenTime = $_SESSION[$this->csrfTokenKey][$token];

            // Check if the token is not expired
            if (($currentTime - $tokenTime) <= $this->tokenExpiry) {
                // Remove the token from the session
                //unset($_SESSION[$this->csrfTokenKey][$token]); // turned off token removal
                $this->cleanUpCsrfTokens();
                return true;
            } else {
                unset($_SESSION[$this->csrfTokenKey][$token]);
                $this->cleanUpCsrfTokens();
                return false;
            }
        }

        // If the token is not in the session clear all tokens and return false 
        $this->cleanUpCsrfTokens();
        return false;

    }

    /**
     * Clean up expired CSRF tokens from the session.
     *
     * @return void
     */
    protected function cleanUpCsrfTokens()
    {
        // Get current timestamp
        $currentTime = time();

        // Check if the CSRF token session key is set and is an array
        if (isset($_SESSION[$this->csrfTokenKey]) && is_array($_SESSION[$this->csrfTokenKey])) {
            foreach ($_SESSION[$this->csrfTokenKey] as $token => $timestamp) {
                // Remove expired tokens
                if (($currentTime - $timestamp) > $this->tokenExpiry) {
                    unset($_SESSION[$this->csrfTokenKey][$token]);
                }
            }
        }
    }

    /**
     * Get all CSRF tokens in the session.
     * 
     * @return array All CSRF tokens.
     */
    public function getAllCsrfTokens()
    {
        return $_SESSION[$this->csrfTokenKey] ?? [];
    }

    /**
     * Set a flash message in the session.
     *
     * @param string $type    Message type (e.g., 'success', 'error').
     * @param string $message The message content.
     * @param array  $data    Additional data.
     * @return void
     */
    public function setFlashMessage($type, $message, $data = [])
    {   
        // Allowed message types
        $allowedTypes = ['success', 'error', 'info', 'warning'];

        // Check if the message type is allowed
        if (!in_array($type, $allowedTypes)) {
            $type = 'info';
        }

        // Add the message to the session
        $_SESSION[$this->flashMessageKey][] = [
            'type'    => $type,
            'message' => $message,
            'data'    => $data,
        ];
    }
    /**
     * Retrieve and clear all flash messages from the session.
     *
     * @return array The flash messages.
     */
    public function getFlashMessages()
    {
        // Get flash messages from the session
        $messages = $_SESSION[$this->flashMessageKey] ?? [];

        // Clear the flash messages
        unset($_SESSION[$this->flashMessageKey]);

        // Return the messages
        return $messages;
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $deleteOldSession Whether to delete the old session data.
     * @return void
     */
    public function regenerateSession($deleteOldSession = false)
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Set session cookie parameters.
     *
     * @param array $params Array of parameters to set.
     * @return void
     */
    public function setSessionCookieParams($params = [])
    {
        $defaults = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        $settings = array_merge($defaults, $params);

        session_set_cookie_params($settings);
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
     * Send a JSON error response and exit.
     *
     * @param string $message The error message.
     * @return void
     */
    public function setJsonErrorMessage($message)
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => $message]);
        exit();
    }

    /**
     * Send a JSON success response and exit.
     *
     * @param string $message The success message.
     * @param array  $data    Additional data.
     * @return void
     */
    public function setJsonSuccessMessage($message, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'msg' => $message, 'data' => $data]);
        exit();
    }

    /**
     * Middleware to verify CSRF tokens on POST requests.
     *
     * @return void
     */
    public function csrfMiddleware()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? '';

            if (!$this->verifyCsrfToken($token)) {
                $this->setErrorMessage('Invalid CSRF token.', '/');
            }
        }
    }

    /**
     * Set an error message and optionally redirect.
     *
     * @param string $message  The error message.
     * @param string $redirect URL to redirect to.
     * @return void
     */
    public function setErrorMessage($message, $redirect = '')
    {
        $this->setFlashMessage('error', $message);
        if (!empty($redirect)) {
            $this->redirect($redirect);
        }
    }

    /**
     * Set a success message and optionally redirect.
     *
     * @param string $message  The success message.
     * @param string $redirect URL to redirect to.
     * @param array  $data     Additional data.
     * @return void
     */
    public function setSuccessMessage($message, $redirect = '', $data = [])
    {
        $this->setFlashMessage('success', $message, $data);
        if (!empty($redirect)) {
            $this->redirect($redirect);
        }
    }
}


