<?php
namespace JiFramework\Core\Security;

use JiFramework\Config\Config;
use JiFramework\Core\Network\HttpRequestHelper;
use JiFramework\Core\Cache\CacheManager;
use JiFramework\Core\Utilities\Environment\EnvironmentHelper;

class AccessControl 
{   
    /**
     * @var bool Whether IP blocking is enabled.
     */
    private $ipBlockingEnabled;

    /**
     * @var string Path to the IP block list.
     */
    private $ipBlockListPath;

    /**
     * @var bool Whether country blocking is enabled.
     */
    private $countryBlockingEnabled;

    /**
     * @var string Path to the country block list.
     */
    private $countryBlockListPath;

    /**
     * @var bool Whether VPN proxy blocking is enabled.
     */
    private $allowVpnProxy;

    /**
     * @var bool Whether TOR proxy blocking is enabled.
     */
    private $apiKey;

    /**
     * @var string API URL for the IP geolocation service.
     */
    private $apiUrl;

    /**
     * @var CacheManager Cache manager instance.
     */
    private $cacheManager;

    /**
     * @var EnvironmentHelper EnvironmentHelper instance.
     */
    private $environment;

    public function __construct()
    {
        // Initialize configurations
        $this->ipBlockingEnabled = Config::IP_BLOCKING_ENABLED;
        $this->ipBlockListPath = Config::IP_BLOCK_LIST_PATH;
        $this->countryBlockingEnabled = Config::COUNTRY_BLOCKING_ENABLED;
        $this->countryBlockListPath = Config::COUNTRY_BLOCK_LIST_PATH;
        $this->allowVpnProxy = Config::ALLOW_VPN_PROXY;
        $this->apiKey = Config::PROXYCHECK_API_KEY;
        $this->apiUrl = Config::PROXYCHECK_API_URL;

        // Initialize CacheManager
        $this->cacheManager = CacheManager::getInstance();

        // Initialize EnvironmentHelper
        $this->environment = new EnvironmentHelper();
    }

    /**
     * Check if the current user's IP is allowed.
     *
     * @return bool
     */
    public function isAccessAllowed()
    {
        $ip = $this->environment->getUserIp();

        // Check if the IP is blocked
        if ($this->ipBlockingEnabled && $this->isIpBlocked($ip)) {
            return false;
        }

        // Check if the country is blocked
        if ($this->countryBlockingEnabled && $this->isCountryBlocked($ip)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the IP is in the block list.
     *
     * @param string $ip
     * @return bool
     */
    private function isIpBlocked($ip)
    {
        if (!file_exists($this->ipBlockListPath)) {
            return false;
        }

        $ipBlockList = json_decode(file_get_contents($this->ipBlockListPath), true);
        if (in_array($ip, $ipBlockList)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the country is in the block list.
     *
     * @param string $ip
     * @return bool
     */
    private function isCountryBlocked($ip)
    {
        $cacheKey = 'ip_info_' . md5($ip);
        $cachedData = $this->cacheManager->get($cacheKey);

        if ($cachedData !== null) {
            $ipInfo = $cachedData;
        } else {
            $ipInfo = $this->getIpInfo($ip);
            if ($ipInfo !== null) {
                // Cache the response for 12 hours
                $this->cacheManager->set($cacheKey, $ipInfo, 12 * 60 * 60);
            } else {
                // If API call fails, allow access
                return false;
            }
        }

        // Check if VPN/proxy is allowed
        if (!$this->allowVpnProxy && isset($ipInfo['proxy']) && $ipInfo['proxy'] === 'yes') {
            return true;
        }

        // Load country block list
        if (!file_exists($this->countryBlockListPath)) {
            return false;
        }

        $countryBlockList = json_decode(file_get_contents($this->countryBlockListPath), true);
        $countryCode = $ipInfo['isocode'] ?? null;

        if ($countryCode !== null && in_array($countryCode, $countryBlockList)) {
            return true;
        }

        return false;
    }

    /**
     * Get IP information from ProxyCheck API.
     *
     * @param string $ip
     * @return array|null
     */
    private function getIpInfo($ip)
    {
        $url = str_replace('{ip}', $ip, $this->apiUrl);
        $url .= '?key=' . $this->apiKey . '&risk=1&vpn=1&asn=1';

        $httpHelper = new HttpRequestHelper();
        $response = $httpHelper->httpGetRequest($url);

        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            if ($data['status'] === 'ok' && isset($data[$ip])) {
                return $data[$ip];
            }
        }

        return null;
    }
}


