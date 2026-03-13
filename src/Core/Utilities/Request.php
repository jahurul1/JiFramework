<?php
namespace JiFramework\Core\Utilities;

use JiFramework\Config\Config;

class Request
{
    // =========================================================================
    // IP detection
    // =========================================================================

    /**
     * Get the client's IP address for the current request.
     *
     * REMOTE_ADDR is used as the authoritative source by default — it is the actual
     * TCP connection IP and cannot be spoofed. HTTP_CLIENT_IP and X-Forwarded-For
     * are user-controlled headers and are intentionally ignored unless REMOTE_ADDR
     * matches a configured trusted proxy (e.g. a load balancer).
     *
     * Configuration (jiconfig.php):
     *   trusted_proxies — array of trusted proxy IPs. When REMOTE_ADDR is one of
     *                     these, X-Forwarded-For is read to find the real client IP.
     *   debug_ip        — override IP for local development testing (development
     *                     mode only; silently ignored in production).
     *
     * @return string Client IP address, or empty string if none can be determined.
     */
    public function getClientIp(): string
    {
        // Development override — lets developers test IP-based features on localhost
        if (Config::$debugIp !== null) {
            if (Config::$appMode === 'development') {
                return Config::$debugIp;
            }
            trigger_error(
                '[JiFramework] debugIp is set but app_mode is not "development" — override ignored.',
                E_USER_WARNING
            );
        }

        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return '';
        }

        // No trusted proxies configured — REMOTE_ADDR is authoritative
        $trustedProxies = Config::$trustedProxies;

        if (empty($trustedProxies) || !in_array($remoteAddr, $trustedProxies, true)) {
            return $remoteAddr;
        }

        // REMOTE_ADDR is a trusted proxy — extract the real client IP from
        // X-Forwarded-For. Format: "client, proxy1, proxy2" (left = oldest hop).
        // Walk right-to-left and return the first IP that is not itself a trusted proxy.
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_reverse(array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $trustedProxies, true)) {
                    return $ip;
                }
            }
        }

        // X-Forwarded-For absent or all IPs are trusted proxies — fall back to REMOTE_ADDR
        return $remoteAddr;
    }

    // =========================================================================
    // Server / environment info
    // =========================================================================

    /**
     * Get server-level information.
     *
     * Note: request_method, request_uri and query_string are intentionally
     * excluded — use getRequestMethod(), $app->url->path(), and
     * $app->url->queryParam() for those.
     *
     * @return array{server_software: string, server_protocol: string, document_root: string, remote_addr: string}
     */
    public function getServerInfo(): array
    {
        return [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? '',
            'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'remote_addr'     => $_SERVER['REMOTE_ADDR'] ?? '',
        ];
    }

    /**
     * Get the PHP version string.
     *
     * @return string e.g. "8.2.12"
     */
    public function getPhpVersion(): string
    {
        return phpversion();
    }

    // =========================================================================
    // Request inspection
    // =========================================================================

    /**
     * Get all HTTP request headers as an associative array.
     *
     * Uses getallheaders() when available (Apache / FPM); falls back to
     * iterating $_SERVER HTTP_* keys for other SAPIs.
     *
     * @return array<string, string>
     */
    public function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $key           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get the HTTP request method in uppercase.
     *
     * @return string e.g. "GET", "POST", "PUT", "DELETE"
     */
    public function getRequestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check whether the current request was made over HTTPS.
     *
     * Handles direct SSL, port 443, and X-Forwarded-Proto (reverse proxies / CDNs).
     *
     * @return bool
     */
    public function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Check whether the request was made via XMLHttpRequest (AJAX).
     *
     * Relies on the X-Requested-With header, which is set automatically by
     * jQuery, Axios, and most HTTP client libraries.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Check whether PHP is running in CLI (command-line) mode.
     *
     * @return bool
     */
    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Get the raw request body.
     *
     * Useful for JSON API endpoints and PUT / PATCH requests where the body
     * is not automatically parsed into $_POST.
     *
     * @return string Raw body, or empty string when there is no body.
     */
    public function getBody(): string
    {
        return (string) file_get_contents('php://input');
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * Returns null when the header is absent, empty, or is not a Bearer scheme.
     *
     * @return string|null
     */
    public function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (stripos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        return null;
    }

    // =========================================================================
    // Environment variables
    // =========================================================================

    /**
     * Get a server / process environment variable.
     *
     * Checks $_ENV first, then falls back to getenv() so it works regardless
     * of the variables_order php.ini setting.
     *
     * @param string $key     Variable name.
     * @param mixed  $default Value returned when the variable is not set.
     * @return mixed
     */
    public function getEnv(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        return ($value !== false && $value !== null) ? $value : $default;
    }
}
