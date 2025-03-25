<?php
/**
 * Test case for the UrlHelper class in the Unit directory
 */

class UnitUrlHelperTest extends TestCase
{
    /**
     * Test the getQueryVariables method of UrlHelper
     */
    public function testGetQueryVariables()
    {
        $url = 'http://example.com/path?param1=value1&param2=value2';
        $variables = $this->app->url->getQueryVariables($url);
        
        $this->assertTrue(is_array($variables), 'Query variables should be returned as an array');
        $this->assertEquals('value1', $variables['param1'], 'First query parameter should be parsed correctly');
        $this->assertEquals('value2', $variables['param2'], 'Second query parameter should be parsed correctly');
    }
    
    /**
     * Test the getDomainInfo method of UrlHelper
     */
    public function testGetDomainInfo()
    {
        $url = 'https://example.com/path';
        $info = $this->app->url->getDomainInfo($url);
        
        $this->assertTrue(is_array($info), 'Domain info should be returned as an array');
        $this->assertEquals('example.com', $info['domain_name'], 'Domain name should be extracted correctly');
        $this->assertTrue(isset($info['domain_ip']), 'Domain IP should be included in the result');
    }
    
    /**
     * Test the isValidUrl method of UrlHelper with valid URLs
     */
    public function testIsValidUrlWithValidUrls()
    {
        $validUrls = [
            'http://example.com',
            'https://www.example.com',
            'http://sub.example.com/path',
            'https://example.com/path?query=value',
            'http://example.com:8080',
            'https://user:pass@example.com'
        ];
        
        foreach ($validUrls as $url) {
            $this->assertTrue(
                $this->app->url->isValidUrl($url), 
                "URL '{$url}' should be considered valid"
            );
        }
    }
    
    /**
     * Test the isValidUrl method of UrlHelper with invalid URLs
     */
    public function testIsValidUrlWithInvalidUrls()
    {
        $invalidUrls = [
            'not a url',
            'http:/example.com', // Missing slash
            'http://example', // Missing TLD
            'ftp://example.com', // Non-HTTP/HTTPS protocol
            'http://', // Missing domain
            '://example.com' // Missing protocol
        ];
        
        foreach ($invalidUrls as $url) {
            $this->assertFalse(
                $this->app->url->isValidUrl($url), 
                "URL '{$url}' should be considered invalid"
            );
        }
    }
    
    /**
     * Test the buildUrl method of UrlHelper
     */
    public function testBuildUrl()
    {
        $baseUrl = 'http://example.com/path';
        $params = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value with spaces'
        ];
        
        $url = $this->app->url->buildUrl($baseUrl, $params);
        
        $this->assertStringContains('http://example.com/path?', $url, 'Base URL should be preserved');
        $this->assertStringContains('param1=value1', $url, 'First parameter should be appended');
        $this->assertStringContains('param2=value2', $url, 'Second parameter should be appended');
        $this->assertStringContains('param3=value+with+spaces', $url, 'Parameter with spaces should be URL encoded');
    }
    
    /**
     * Test the parseUrlComponents method of UrlHelper
     */
    public function testParseUrlComponents()
    {
        $url = 'https://user:pass@example.com:8080/path/to/page?query=value#fragment';
        $components = $this->app->url->parseUrlComponents($url);
        
        $this->assertTrue(is_array($components), 'Components should be returned as an array');
        $this->assertEquals('https', $components['scheme'], 'Scheme should be parsed correctly');
        $this->assertEquals('user', $components['user'], 'User should be parsed correctly');
        $this->assertEquals('pass', $components['pass'], 'Password should be parsed correctly');
        $this->assertEquals('example.com', $components['host'], 'Host should be parsed correctly');
        $this->assertEquals(8080, $components['port'], 'Port should be parsed correctly');
        $this->assertEquals('/path/to/page', $components['path'], 'Path should be parsed correctly');
        $this->assertEquals('query=value', $components['query'], 'Query should be parsed correctly');
        $this->assertEquals('fragment', $components['fragment'], 'Fragment should be parsed correctly');
    }
} 


