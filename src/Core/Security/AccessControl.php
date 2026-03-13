<?php
namespace JiFramework\Core\Security;

use JiFramework\Config\Config;
use JiFramework\Core\Network\HttpClient;
use JiFramework\Core\Cache\CacheManager;
use JiFramework\Core\Utilities\Request;

class AccessControl
{
    /**
     * @var bool Whether IP blocking is enabled.
     */
    private $ipBlockingEnabled;

    /**
     * @var string Path to the IP block list JSON file.
     */
    private $ipBlockListPath;

    /**
     * @var bool Whether country blocking is enabled.
     */
    private $countryBlockingEnabled;

    /**
     * @var string Path to the country block list JSON file.
     */
    private $countryBlockListPath;

    /**
     * @var bool Whether VPN / proxy access is permitted.
     */
    private $allowVpnProxy;

    /**
     * @var string ProxyCheck API key.
     */
    private $apiKey;

    /**
     * @var string ProxyCheck API URL template (contains {ip} placeholder).
     */
    private $apiUrl;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Request
     */
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request                = $request;
        $this->ipBlockingEnabled      = Config::$ipBlockingEnabled;
        $this->ipBlockListPath        = Config::$ipBlockListPath;
        $this->countryBlockingEnabled = Config::$countryBlockingEnabled;
        $this->countryBlockListPath   = Config::$countryBlockListPath;
        $this->allowVpnProxy          = Config::$allowVpnProxy;
        $this->apiKey                 = Config::$proxycheckApiKey;
        $this->apiUrl                 = Config::$proxycheckApiUrl;
        $this->cacheManager           = CacheManager::getInstance();
        $this->httpClient             = new HttpClient();
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Check whether the current request's IP is allowed through all active gates.
     *
     * Gates run in this order:
     *   1. IP block list (local file check — no API call)
     *   2. VPN / proxy check (ProxyCheck API — only when allowVpnProxy = false)
     *   3. Country block list (ProxyCheck API — only when countryBlockingEnabled = true)
     *
     * When the ProxyCheck API is unreachable the method fails open (allows access)
     * rather than blocking legitimate users.
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        $ip = $this->request->getClientIp();

        // Gate 1: IP block list
        if ($this->ipBlockingEnabled && $this->isIpBlocked($ip)) {
            return false;
        }

        // Gates 2 & 3 both require a ProxyCheck API lookup — share a single call
        $needsApiCheck = $this->countryBlockingEnabled || !$this->allowVpnProxy;

        if ($needsApiCheck) {
            $ipInfo = $this->getIpInfo($ip);

            if ($ipInfo !== null) {
                // Gate 2: VPN / proxy
                if (!$this->allowVpnProxy && $this->isVpnOrProxy($ipInfo)) {
                    return false;
                }

                // Gate 3: Country
                if ($this->countryBlockingEnabled && $this->isCountryInBlockList($ipInfo)) {
                    return false;
                }
            }
            // API failure → fail open (allow access)
        }

        return true;
    }

    /**
     * Add an IP address to the block list.
     * The operation is idempotent — adding an already-blocked IP returns true.
     *
     * @param string $ip
     * @return bool True on success, false when $ip is not a valid IP address or the file cannot be written.
     */
    public function blockIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $list = $this->loadJsonFile($this->ipBlockListPath) ?? [];

        if (in_array($ip, $list, true)) {
            return true; // Already blocked — idempotent
        }

        $list[] = $ip;

        return $this->saveJsonFile($this->ipBlockListPath, $list);
    }

    /**
     * Remove an IP address from the block list.
     * The operation is idempotent — removing an IP that is not in the list returns true.
     *
     * @param string $ip
     * @return bool True on success, false when the file cannot be written.
     */
    public function unblockIp(string $ip): bool
    {
        $list = $this->loadJsonFile($this->ipBlockListPath);

        if ($list === null) {
            // File missing or empty — nothing to unblock
            return true;
        }

        $filtered = array_values(array_filter($list, fn($item) => $item !== $ip));

        return $this->saveJsonFile($this->ipBlockListPath, $filtered);
    }

    // =========================================================================
    // Private — gate checks
    // =========================================================================

    /**
     * Check if the IP is present in the local block list file.
     *
     * @param string $ip
     * @return bool
     */
    private function isIpBlocked(string $ip): bool
    {
        $list = $this->loadJsonFile($this->ipBlockListPath);

        return is_array($list) && in_array($ip, $list, true);
    }

    /**
     * Check if the IP info returned by ProxyCheck indicates a VPN or proxy.
     *
     * @param array $ipInfo
     * @return bool
     */
    private function isVpnOrProxy(array $ipInfo): bool
    {
        return isset($ipInfo['proxy']) && $ipInfo['proxy'] === 'yes';
    }

    /**
     * Check if the IP's country code is in the country block list file.
     *
     * @param array $ipInfo
     * @return bool
     */
    private function isCountryInBlockList(array $ipInfo): bool
    {
        $list = $this->loadJsonFile($this->countryBlockListPath);

        if (!is_array($list)) {
            return false;
        }

        $code = $ipInfo['isocode'] ?? null;

        return $code !== null && in_array($code, $list, true);
    }

    // =========================================================================
    // Private — ProxyCheck API
    // =========================================================================

    /**
     * Fetch IP information from the ProxyCheck API, with 12-hour caching.
     * Returns null when the API is unreachable or returns an unexpected response.
     *
     * @param string $ip
     * @return array|null
     */
    private function getIpInfo(string $ip): ?array
    {
        $cacheKey = 'ip_info_' . md5($ip);
        $cached   = $this->cacheManager->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $url      = str_replace('{ip}', $ip, $this->apiUrl);
        $url     .= '?key=' . $this->apiKey . '&risk=1&vpn=1&asn=1';
        $response = $this->httpClient->get($url);

        if ($response['status_code'] !== 200) {
            return null;
        }

        $data = json_decode($response['body'], true);

        if (!is_array($data) || ($data['status'] ?? '') !== 'ok' || !isset($data[$ip])) {
            return null;
        }

        $ipInfo = $data[$ip];
        $this->cacheManager->set($cacheKey, $ipInfo, 12 * 3600);

        return $ipInfo;
    }

    // =========================================================================
    // Private — helpers
    // =========================================================================

    /**
     * Load and decode a JSON file.
     * Returns the decoded array, or null when the file does not exist,
     * is empty, or contains invalid JSON.
     *
     * @param string $path
     * @return array|null
     */
    private function loadJsonFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Encode an array as pretty-printed JSON and write it to a file.
     * Creates parent directories if they do not exist.
     *
     * @param string $path
     * @param array  $data
     * @return bool
     */
    private function saveJsonFile(string $path, array $data): bool
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }
}
