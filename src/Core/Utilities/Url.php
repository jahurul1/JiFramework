<?php
namespace JiFramework\Core\Utilities;

class Url
{
    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Detect whether the current request is HTTPS.
     * Handles direct SSL, port 443, and reverse-proxy forwarding headers.
     */
    private function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    }

    // =========================================================================
    // Current request
    // =========================================================================

    /**
     * Get the full current page URL (scheme + host + path + query string).
     *
     * @return string
     */
    public function current(): string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';
        $host     = $_SERVER['HTTP_HOST']   ?? '';
        $uri      = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . $host . $uri;
    }

    /**
     * Get the host URL (scheme + host only, no path or query string).
     *
     * @return string
     */
    public function host(): string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';

        return $protocol . ($_SERVER['HTTP_HOST'] ?? '');
    }

    /**
     * Get the current request path (REQUEST_URI — path + query string, no host).
     *
     * @return string
     */
    public function path(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the HTTP referrer, or null if not set.
     *
     * @return string|null
     */
    public function referrer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    // =========================================================================
    // Query parameters
    // =========================================================================

    /**
     * Get a single query parameter from the current request.
     * Returns $default when the key is absent.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function queryParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Parse all query parameters from a URL string into an associative array.
     *
     * @param string $url
     * @return array
     */
    public function queryParams(string $url): array
    {
        $parts = parse_url($url);
        $vars  = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $vars);
        }

        return $vars;
    }

    // =========================================================================
    // URL building and manipulation
    // =========================================================================

    /**
     * Build a URL by appending query parameters to a base URL.
     * Correctly appends with ? or & depending on whether a query string already exists.
     *
     * @param string $baseUrl
     * @param array  $params
     * @return string
     */
    public function build(string $baseUrl, array $params = []): string
    {
        $queryString = http_build_query($params);

        if ($queryString === '') {
            return $baseUrl;
        }

        $separator = strpos($baseUrl, '?') === false ? '?' : '&';

        return $baseUrl . $separator . $queryString;
    }

    /**
     * Remove a specific query parameter from a URL.
     * Preserves scheme, host, port, path, remaining parameters, and fragment.
     *
     * @param string $url
     * @param string $key
     * @return string
     */
    public function removeParam(string $url, string $key): string
    {
        $parts = parse_url($url);

        if (!isset($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $params);
        unset($params[$key]);

        // Reconstruct the URL
        $result = '';

        if (isset($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $result .= $parts['user'];
            if (isset($parts['pass'])) {
                $result .= ':' . $parts['pass'];
            }
            $result .= '@';
        }
        if (isset($parts['host'])) {
            $result .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }

        $result .= $parts['path'] ?? '';

        if (!empty($params)) {
            $result .= '?' . http_build_query($params);
        }

        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
    }

    // =========================================================================
    // Inspection and validation
    // =========================================================================

    /**
     * Validate whether a string is a well-formed HTTP or HTTPS URL.
     * Accepts localhost and IP addresses (v4 and v6).
     *
     * @param string $url
     * @return bool
     */
    public function isValid(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        if (!isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'];

        // IPv4 and IPv6 addresses are valid hosts
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        // localhost is valid
        if ($host === 'localhost') {
            return true;
        }

        // Domain names: require at least one dot and an alphabetic TLD (min 2 chars)
        $segments = explode('.', $host);
        if (count($segments) < 2) {
            return false;
        }

        $tld = end($segments);

        return (bool) preg_match('/^[a-zA-Z]{2,}$/', $tld);
    }

    /**
     * Get domain name and resolved IP address for a URL.
     * Returns null when the URL is malformed (no host component).
     * Returns ['domain_name' => string, 'domain_ip' => string|null] on success;
     * domain_ip is null when DNS resolution fails.
     *
     * @param string $url
     * @return array|null
     */
    public function domainInfo(string $url): ?array
    {
        $parts = parse_url($url);

        if (!isset($parts['host'])) {
            return null;
        }

        $domain = $parts['host'];
        $ip     = gethostbyname($domain);

        return [
            'domain_name' => $domain,
            'domain_ip'   => ($ip !== $domain) ? $ip : null,
        ];
    }
}
