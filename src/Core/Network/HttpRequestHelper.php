<?php
namespace JiFramework\Core\Network;

class HttpRequestHelper {
    /**
     * Flag to control debug output
     * 
     * @var bool
     */
    private $debug = false;
    
    /**
     * Set debug mode
     * 
     * @param bool $debug Whether to enable debug output
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }
    
    /**
     * Internal method to print debug information
     * 
     * @param mixed $data The data to print
     * @return void
     */
    private function debugPrint($data)
    {
        if ($this->debug) {
            if (is_array($data) || is_object($data)) {
                print_r($data);
            } else {
                echo $data . "\n";
            }
        }
    }

    /**
     * Performs an HTTP POST request to a specified URL.
     *
     * @param string $url      The URL to which the request will be sent.
     * @param array  $postData The data to be sent with the request.
     * @param array  $options  Additional options for the request.
     * @return array|false    The response body from the target URL, or false on failure.
     */
    public function httpPostRequest($url, $postData = [], $options = [])
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Disable or enable SSL verification
        if (isset($options['ssl_verify']) && !$options['ssl_verify']) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set custom headers if provided
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Set timeout if provided
        if (isset($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // Execute the request
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            // Handle error; optionally log or return error message
            curl_close($curl);
            return false;
        }

        // Get HTTP status code
         $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL session and return response
        curl_close($curl);
        return [
            'status_code' => $statusCode,
            'body' => $response
        ];
    }

    /**
     * Performs an HTTP GET request to a specified URL.
     *
     * @param string $url     The URL from which the data will be retrieved.
     * @param array  $options Additional options for the request.
     * @return array|false   The response body from the target URL, or false on failure.
     */
    public function httpGetRequest($url, $options = [])
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Disable or enable SSL verification
        if (isset($options['ssl_verify']) && !$options['ssl_verify']) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set custom headers if provided
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Set timeout if provided
        if (isset($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // Execute the request
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            // Handle error; optionally log or return error message
            curl_close($curl);
            return false;
        }

        // Get HTTP status code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL session and return response
        curl_close($curl);

        // Only print response if debug is enabled
        $this->debugPrint($response);

        return [
            'status_code' => $statusCode,
            'body' => $response
        ];
    }

    /**
     * Performs an HTTP request with a custom method.
     *
     * @param string $method  The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param string $url     The URL to which the request will be sent.
     * @param array  $data    The data to send with the request.
     * @param array  $options Additional options for the request.
     * @return array|false   The response body from the target URL, or false on failure.
     */
    public function httpRequest($method, $url, $data = [], $options = [])
    {
        $curl = curl_init();

        // Set cURL options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // Disable or enable SSL verification
        if (isset($options['ssl_verify']) && !$options['ssl_verify']) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set custom headers if provided
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Set timeout if provided
        if (isset($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // Execute the request
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            // Handle error; optionally log or return error message
            curl_close($curl);
            return false;
        }

        // Get HTTP status code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL session and return response
        curl_close($curl);
        return [
            'status_code' => $statusCode,
            'body' => $response
        ];
    }
}


