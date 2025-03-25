<?php
namespace JiFramework\Core\Network;

class UrlHelper 
{
    /**
     * Get the full current page URL.
     *
     * @return string The full URL.
     */
    public function getFullPageURL()
    {
        // Check for HTTPS or port 443 for secure connections
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;

        // Determine the protocol part of the URL
        $protocol = $isSecure ? 'https://' : 'http://';

        // Construct the full URL
        $actualLink = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        return $actualLink;
    }

    /**
     * Get the host URL (protocol and host).
     *
     * @return string The host URL.
     */
    public function getHostURL()
    {
        // Enhanced HTTPS detection
        $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');

        // Determine protocol
        $protocol = $isSecure ? 'https://' : 'http://';

        // Construct and return the host URL
        $hostURL = $protocol . $_SERVER['HTTP_HOST'];
        return $hostURL;
    }

    /**
     * Get the referring URL.
     *
     * @return string|null The referrer URL or null if not set.
     */
    public function getReferrerUrl()
    {
        // Check if the HTTP_REFERER is set and return it, otherwise return null
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

        return $httpReferer;
    }

     /**
     * Get domain information from a URL.
     *
     * @param string $url The URL to parse.
     * @return array|false Associative array with 'domain_name' and 'domain_ip' or false on failure.
     */
    public function getDomainInfo($url)
    {
        // Parse the URL to extract the host component, which contains the domain name
        $pieces = parse_url($url);
        if (!isset($pieces['host'])) {
            // If the URL is malformed or the host component is missing, return false
            return false;
        }

        $domain    = $pieces['host'];          // Extracted domain name
        $ipAddress = gethostbyname($domain);   // Resolves the domain name to an IPv4 address

        // Return an associative array with the domain name and its corresponding IP address
        return [
            'domain_name' => $domain,
            'domain_ip'   => $ipAddress,
        ];
    }

    /**
     * Get query variables from a URL.
     *
     * @param string $url The URL to parse.
     * @return array An associative array of query variables.
     */
    public function getQueryVariables($url)
    {
        // Parse the URL to extract its components
        $parts = parse_url($url);

        // Initialize an empty array to hold query variables
        $queryVariables = [];

        // Check if the 'query' part exists in the parsed URL
        if (isset($parts['query'])) {
            // Parse the query string into an associative array
            parse_str($parts['query'], $queryVariables);
        }

        // Return the associative array of query variables (empty if no query string was found)
        return $queryVariables;
    }

    /**
     * Build a URL with query parameters.
     *
     * @param string $baseUrl   The base URL.
     * @param array  $params    Associative array of query parameters.
     * @return string           The full URL with query parameters.
     */
    public function buildUrl($baseUrl, $params = [])
    {
        $queryString = http_build_query($params);
        $separator   = strpos($baseUrl, '?') === false ? '?' : '&';
        return $baseUrl . ($queryString ? $separator . $queryString : '');
    }

    /**
     * Validate if a string is a valid URL.
     *
     * @param string $url The URL to validate.
     * @return bool       True if valid, false otherwise.
     */
    public function isValidUrl($url)
    {
        // First use PHP's filter_var for basic validation
        $basicValidation = filter_var($url, FILTER_VALIDATE_URL) !== false;
        
        if (!$basicValidation) {
            return false;
        }
        
        // Parse the URL to get its components
        $parts = parse_url($url);
        
        // Check for required components
        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return false;
        }
        
        // Only allow HTTP or HTTPS schemes
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'])) {
            return false;
        }
        
        // Check if the host has at least one dot and a valid TLD
        $hostParts = explode('.', $parts['host']);
        if (count($hostParts) < 2) {
            return false; // No dot in hostname
        }
        
        // Get the TLD (last part after the dot)
        $tld = end($hostParts);
        
        // TLD should be at least 2 characters
        if (strlen($tld) < 2) {
            return false;
        }
        
        return true;
    }

    /**
     * Parse URL components.
     *
     * @param string $url The URL to parse.
     * @return array|false An associative array of URL components or false on failure.
     */
    public function parseUrlComponents($url)
    {
        return parse_url($url);
    }
}


