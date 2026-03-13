<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Core\Utilities\Url;
use JiFramework\Tests\TestCase;

class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_SERVER['HTTP_HOST']      = 'example.com';
        $_SERVER['REQUEST_URI']    = '/path/page?foo=bar&baz=qux';
        $_SERVER['HTTPS']          = 'off';
        $_SERVER['SERVER_PORT']    = '80';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['foo' => 'bar', 'baz' => 'qux'];
    }

    private function url(): Url
    {
        return new Url();
    }

    // ── current() ────────────────────────────────────────────────────────────

    public function testCurrent(): void
    {
        $current = $this->url()->current();
        $this->assertStringContainsString('example.com', $current);
        $this->assertStringStartsWith('http://', $current);
    }

    // ── host() — returns scheme + host ───────────────────────────────────────

    public function testHost(): void
    {
        // host() returns 'http://example.com' (scheme + host, no path)
        $this->assertSame('http://example.com', $this->url()->host());
    }

    public function testHostHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('https://example.com', $this->url()->host());
        $_SERVER['HTTPS'] = 'off';
    }

    // ── path() — returns REQUEST_URI (path + query string) ───────────────────

    public function testPath(): void
    {
        // path() returns REQUEST_URI as-is, including query string
        $this->assertSame('/path/page?foo=bar&baz=qux', $this->url()->path());
    }

    // ── queryParam() — reads from $_GET ──────────────────────────────────────

    public function testQueryParam(): void
    {
        $this->assertSame('bar', $this->url()->queryParam('foo'));
    }

    public function testQueryParamDefault(): void
    {
        $this->assertSame('default', $this->url()->queryParam('missing', 'default'));
    }

    // ── queryParams($url) — parses a URL string ───────────────────────────────

    public function testQueryParams(): void
    {
        $params = $this->url()->queryParams('https://example.com/search?q=php&page=2');
        $this->assertIsArray($params);
        $this->assertSame('php', $params['q']);
        $this->assertSame('2', $params['page']);
    }

    public function testQueryParamsEmptyForNoQueryString(): void
    {
        $params = $this->url()->queryParams('https://example.com/path');
        $this->assertSame([], $params);
    }

    // ── build() ──────────────────────────────────────────────────────────────

    public function testBuild(): void
    {
        $url = $this->url()->build('https://example.com/search', ['q' => 'php', 'page' => 2]);
        $this->assertStringContainsString('q=php', $url);
        $this->assertStringContainsString('page=2', $url);
        $this->assertStringStartsWith('https://example.com/search', $url);
    }

    // ── removeParam() ────────────────────────────────────────────────────────

    public function testRemoveParam(): void
    {
        $result = $this->url()->removeParam('https://example.com?a=1&b=2&c=3', 'b');
        $this->assertStringNotContainsString('b=2', $result);
        $this->assertStringContainsString('a=1', $result);
    }

    // ── isValid() ────────────────────────────────────────────────────────────

    public function testIsValidUrl(): void
    {
        $this->assertTrue($this->url()->isValid('https://example.com'));
        $this->assertTrue($this->url()->isValid('http://localhost'));
        $this->assertTrue($this->url()->isValid('http://192.168.1.1'));
        $this->assertFalse($this->url()->isValid('not a url'));
        $this->assertFalse($this->url()->isValid(''));
    }

    // ── referrer() ───────────────────────────────────────────────────────────

    public function testReferrerReturnsNullWhenNotSet(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        $this->assertNull($this->url()->referrer());
    }

    public function testReferrerReturnsHeaderValue(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://google.com';
        $this->assertSame('https://google.com', $this->url()->referrer());
        unset($_SERVER['HTTP_REFERER']);
    }

    // ── domainInfo() — returns domain_name, domain_ip ────────────────────────

    public function testDomainInfoValidUrl(): void
    {
        $info = $this->url()->domainInfo('https://www.example.com/path?q=1');
        $this->assertIsArray($info);
        $this->assertArrayHasKey('domain_name', $info);
        $this->assertSame('www.example.com', $info['domain_name']);
    }

    public function testDomainInfoMalformedReturnsNull(): void
    {
        $this->assertNull($this->url()->domainInfo('not-a-url'));
    }
}
