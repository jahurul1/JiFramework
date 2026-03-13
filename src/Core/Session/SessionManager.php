<?php
namespace JiFramework\Core\Session;

use JiFramework\Config\Config;

class SessionManager
{
    /**
     * Session key for CSRF tokens.
     * @var string
     */
    protected $csrfTokenKey;

    /**
     * Session key for flash messages.
     * @var string
     */
    protected $flashMessageKey;

    /**
     * CSRF token expiration time in seconds.
     * @var int
     */
    protected $tokenExpiry;

    /**
     * Maximum number of CSRF tokens to keep in session.
     * @var int
     */
    protected $maxTokens;

    /**
     * Length of generated CSRF tokens in bytes.
     * @var int
     */
    protected $tokenLength;

    public function __construct()
    {
        $this->csrfTokenKey    = Config::$csrfTokenKey;
        $this->flashMessageKey = Config::$flashMessageKey;
        $this->tokenExpiry     = Config::$csrfTokenExpiry;
        $this->maxTokens       = Config::$csrfTokenLimit;
        $this->tokenLength     = Config::$csrfTokenLength;
    }

    // =========================================================================
    // Core session operations
    // =========================================================================

    /**
     * Start the session if it is not already active.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Returns true if the session is currently active.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Returns the current session ID.
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Store a value in the session.
     *
     * @param string $key   Session key.
     * @param mixed  $value Any serialisable PHP value.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key.
     * @param mixed  $default Returned when key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Returns true if the session key exists (even if its value is null).
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION ?? []);
    }

    /**
     * Remove a single key from the session.
     */
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Return the entire session data as an array.
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Clear all session data without destroying the session itself.
     * The session ID remains the same.
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Fully destroy the session: clears data, expires the cookie, and calls session_destroy().
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // =========================================================================
    // Session configuration
    // =========================================================================

    /**
     * Regenerate the session ID. Call after privilege escalation (e.g. login)
     * to prevent session fixation attacks.
     *
     * @param bool $deleteOldSession Whether to delete the old session data.
     */
    public function regenerateSession(bool $deleteOldSession = true): bool
    {
        if (headers_sent()) {
            trigger_error(
                '[JiFramework] regenerateSession() has no effect after headers have already been sent.',
                E_USER_WARNING
            );
            return false;
        }

        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Configure session cookie parameters.
     *
     * IMPORTANT: Must be called BEFORE session_start() — i.e., before new App().
     * Has no effect if the session is already active.
     *
     * @param array $params Override any of: lifetime, path, domain, secure, httponly, samesite.
     */
    public function setSessionCookieParams(array $params = []): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            trigger_error(
                '[JiFramework] setSessionCookieParams() has no effect after session_start(). '
                . 'Call it before new App().',
                E_USER_WARNING
            );
            return;
        }

        $defaults = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        session_set_cookie_params(array_merge($defaults, $params));
    }

    // =========================================================================
    // CSRF protection
    // =========================================================================

    /**
     * Generate a new CSRF token, store it in the session, and return it.
     * Up to $maxTokens are kept simultaneously (oldest removed when limit reached).
     */
    public function generateCsrfToken(): string
    {
        $token     = bin2hex(random_bytes($this->tokenLength));
        $timestamp = time();

        if (!isset($_SESSION[$this->csrfTokenKey]) || !is_array($_SESSION[$this->csrfTokenKey])) {
            $_SESSION[$this->csrfTokenKey] = [];
        }

        if (count($_SESSION[$this->csrfTokenKey]) >= $this->maxTokens) {
            asort($_SESSION[$this->csrfTokenKey]);
            array_shift($_SESSION[$this->csrfTokenKey]);
        }

        $_SESSION[$this->csrfTokenKey][$token] = $timestamp;

        return $token;
    }

    /**
     * Verify a CSRF token against the session store.
     *
     * If $token is empty, falls back to the X-CSRF-TOKEN request header
     * automatically — this supports AJAX requests that send the token as a header
     * instead of a POST field.
     *
     * @param string $token The token to verify (from $_POST or empty for header fallback).
     * @return bool True if valid and not expired.
     */
    public function verifyCsrfToken(string $token = ''): bool
    {
        if (!$token) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        $currentTime = time();

        if (isset($_SESSION[$this->csrfTokenKey][$token])) {
            $tokenTime = $_SESSION[$this->csrfTokenKey][$token];

            if (($currentTime - $tokenTime) <= $this->tokenExpiry) {
                $this->cleanUpCsrfTokens();
                return true;
            }

            unset($_SESSION[$this->csrfTokenKey][$token]);
            $this->cleanUpCsrfTokens();
            return false;
        }

        $this->cleanUpCsrfTokens();
        return false;
    }

    /**
     * CSRF middleware. Call at the top of any POST-handling page or route.
     *
     * On failure:
     * - AJAX / JSON requests  → HTTP 403 JSON response then exit.
     * - Regular form requests → flash error message, redirect to $redirectOnFail, then exit.
     *
     * @param string $redirectOnFail URL to redirect to on CSRF failure (regular forms only).
     */
    public function csrfMiddleware(string $redirectOnFail = '/'): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['_csrf_token'] ?? '';

        if ($this->verifyCsrfToken($token)) {
            return;
        }

        $isAjax = (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' ||
            str_contains($_SERVER['HTTP_ACCEPT']       ?? '', 'application/json') ||
            str_contains($_SERVER['CONTENT_TYPE']      ?? '', 'application/json')
        );

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'CSRF token invalid or expired.']);
            exit();
        }

        $this->flashError('Your session has expired. Please try again.');
        header('Location: ' . $redirectOnFail);
        exit();
    }

    /**
     * Remove expired tokens from the CSRF token store.
     */
    protected function cleanUpCsrfTokens(): void
    {
        $currentTime = time();

        if (isset($_SESSION[$this->csrfTokenKey]) && is_array($_SESSION[$this->csrfTokenKey])) {
            foreach ($_SESSION[$this->csrfTokenKey] as $token => $timestamp) {
                if (($currentTime - $timestamp) > $this->tokenExpiry) {
                    unset($_SESSION[$this->csrfTokenKey][$token]);
                }
            }
        }
    }

    // =========================================================================
    // Flash messages
    // =========================================================================

    /**
     * Store a flash message in the session.
     * Flash messages are retrieved once via getFlashMessages() and then cleared.
     *
     * @param string $type    One of: 'success', 'error', 'info', 'warning'.
     * @param string $message The message text.
     * @param array  $data    Optional extra data attached to the message.
     */
    public function setFlashMessage(string $type, string $message, array $data = []): void
    {
        $allowedTypes = ['success', 'error', 'info', 'warning'];

        if (!in_array($type, $allowedTypes)) {
            $type = 'info';
        }

        $_SESSION[$this->flashMessageKey][] = [
            'type'    => $type,
            'message' => $message,
            'data'    => $data,
        ];
    }

    /**
     * Retrieve all pending flash messages and clear them from the session.
     *
     * @return array Array of ['type', 'message', 'data'] entries.
     */
    public function getFlashMessages(): array
    {
        $messages = $_SESSION[$this->flashMessageKey] ?? [];
        unset($_SESSION[$this->flashMessageKey]);
        return $messages;
    }

    /**
     * Store an error flash message.
     */
    public function flashError(string $message, array $data = []): void
    {
        $this->setFlashMessage('error', $message, $data);
    }

    /**
     * Store a success flash message.
     */
    public function flashSuccess(string $message, array $data = []): void
    {
        $this->setFlashMessage('success', $message, $data);
    }

    /**
     * Store an info flash message.
     */
    public function flashInfo(string $message, array $data = []): void
    {
        $this->setFlashMessage('info', $message, $data);
    }

    /**
     * Store a warning flash message.
     */
    public function flashWarning(string $message, array $data = []): void
    {
        $this->setFlashMessage('warning', $message, $data);
    }
}
