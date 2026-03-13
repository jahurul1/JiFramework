<?php
namespace JiFramework\Core\Network;

class HttpClient
{
    /**
     * @var bool Enable cURL verbose debug output.
     */
    private $debug = false;

    /**
     * Enable or disable debug output.
     *
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Perform a GET request.
     *
     * @param string $url
     * @param array  $options  See buildCurl() for supported keys.
     * @return array  Always returns ['status_code', 'body', 'headers', 'error'].
     */
    public function get(string $url, array $options = []): array
    {
        $curl = $this->buildCurl($url, $options);
        if ($curl === false) {
            return $this->errorResponse('cURL failed to initialize');
        }

        return $this->executeCurl($curl);
    }

    /**
     * Perform a POST request.
     *
     * @param string $url
     * @param array  $data     Body data. Form-encoded by default; JSON if options['json'] = true.
     * @param array  $options  See buildCurl() for supported keys.
     * @return array
     */
    public function post(string $url, array $data = [], array $options = []): array
    {
        $curl = $this->buildCurl($url, $options);
        if ($curl === false) {
            return $this->errorResponse('cURL failed to initialize');
        }

        curl_setopt($curl, CURLOPT_POST, true);
        $this->applyBody($curl, $data, $options);

        return $this->executeCurl($curl);
    }

    /**
     * Perform a request with any HTTP method (PUT, PATCH, DELETE, etc.).
     *
     * @param string $method  HTTP verb (case-insensitive).
     * @param string $url
     * @param array  $data    Body data. Form-encoded by default; JSON if options['json'] = true.
     * @param array  $options See buildCurl() for supported keys.
     * @return array
     */
    public function request(string $method, string $url, array $data = [], array $options = []): array
    {
        $curl = $this->buildCurl($url, $options);
        if ($curl === false) {
            return $this->errorResponse('cURL failed to initialize');
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        $this->applyBody($curl, $data, $options);

        return $this->executeCurl($curl);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Initialise a cURL handle with all shared options applied.
     *
     * Supported $options keys:
     *   headers          (array)  Custom HTTP headers, e.g. ['Authorization: Bearer token'].
     *   timeout          (int)    Total request timeout in seconds (CURLOPT_TIMEOUT).
     *   connect_timeout  (int)    Connection timeout in seconds (CURLOPT_CONNECTTIMEOUT).
     *   ssl_verify       (bool)   Verify SSL peer and host. Default: true.
     *   follow_redirects (bool)   Follow HTTP redirects. Default: true.
     *
     * @param string $url
     * @param array  $options
     * @return resource|false cURL handle or false on failure.
     */
    private function buildCurl(string $url, array $options)
    {
        $curl = curl_init();
        if ($curl === false) {
            return false;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Follow redirects (enabled by default)
        $followRedirects = $options['follow_redirects'] ?? true;
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $followRedirects);
        if ($followRedirects) {
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        }

        // SSL verification (enabled by default)
        $sslVerify = $options['ssl_verify'] ?? true;
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);

        // Timeouts
        if (isset($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, (int)$options['timeout']);
        }
        if (isset($options['connect_timeout'])) {
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, (int)$options['connect_timeout']);
        }

        // Custom headers
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Debug
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        return $curl;
    }

    /**
     * Apply request body — JSON or form-encoded — and update Content-Type header.
     *
     * Supported $options keys:
     *   json  (bool)  When true, encodes $data as JSON and sets Content-Type: application/json.
     *
     * @param resource $curl
     * @param array    $data
     * @param array    $options
     * @return void
     */
    private function applyBody($curl, array $data, array $options): void
    {
        if (empty($data)) {
            return;
        }

        if (!empty($options['json'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

            // Merge Content-Type into headers, preserving any existing custom headers
            $headers = $options['headers'] ?? [];
            $hasContentType = false;
            foreach ($headers as $h) {
                if (stripos($h, 'content-type:') === 0) {
                    $hasContentType = true;
                    break;
                }
            }
            if (!$hasContentType) {
                $headers[] = 'Content-Type: application/json';
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    /**
     * Execute the cURL handle and return a normalised response array.
     *
     * @param resource $curl
     * @return array ['status_code', 'body', 'headers', 'error']
     */
    private function executeCurl($curl): array
    {
        // Collect response headers via callback
        $responseHeaders = [];
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $len   = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
            return $len;
        });

        $body       = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError  = curl_errno($curl) ? curl_error($curl) : null;

        curl_close($curl);

        $result = [
            'status_code' => $statusCode,
            'body'        => $curlError ? '' : (string)$body,
            'headers'     => $responseHeaders,
            'error'       => $curlError,
        ];

        if ($this->debug) {
            $this->debugPrint($result);
        }

        return $result;
    }

    /**
     * Build a normalised error response for failures before cURL executes.
     *
     * @param string $error
     * @return array
     */
    private function errorResponse(string $error): array
    {
        return [
            'status_code' => 0,
            'body'        => '',
            'headers'     => [],
            'error'       => $error,
        ];
    }

    /**
     * Print debug data to output.
     *
     * @param mixed $data
     * @return void
     */
    private function debugPrint($data): void
    {
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            echo $data . "\n";
        }
    }
}
