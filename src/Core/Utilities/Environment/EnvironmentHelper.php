<?php
namespace JiFramework\Core\Utilities\Environment;

class EnvironmentHelper
{
    /**
     * Get the name of the current script.
     *
     * @return string The script name.
     */
    public function getCurrentScriptName()
    {
        return basename($_SERVER['PHP_SELF']);
    }

    /**
     * Get the user's IP address.
     *
     * @return string The user's IP address.
     */
    public function getUserIp()
    {
        // Check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IPs passing through proxies
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Might contain multiple IPs in the format: "client IP, proxy 1 IP, proxy 2 IP"
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($forwardedIps as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip; // Return the first valid IP found
                }
            }
        }
        // Default to REMOTE_ADDR if no other valid IP address is found
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        // Return an empty string if no valid IP address is found
        return '';
    }

    /**
     * Get server information.
     *
     * @return array An associative array of server information.
     */
    public function getServerInfo()
    {
        return [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? '',
            'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'remote_addr'     => $_SERVER['REMOTE_ADDR'] ?? '',
            'request_method'  => $_SERVER['REQUEST_METHOD'] ?? '',
            'request_uri'     => $_SERVER['REQUEST_URI'] ?? '',
            'query_string'    => $_SERVER['QUERY_STRING'] ?? '',
        ];
    }

    /**
     * Get PHP version.
     *
     * @return string The PHP version.
     */
    public function getPhpVersion()
    {
        return phpversion();
    }

    /**
     * Get request headers.
     *
     * @return array An associative array of request headers.
     */
    public function getRequestHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        } else {
            // Fallback for servers without getallheaders()
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName          = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                }
            }
            return $headers;
        }
    }

    /**
     * Get request method.
     *
     * @return string The request method (GET, POST, etc.).
     */
    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}


