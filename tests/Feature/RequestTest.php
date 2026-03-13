<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Core\Utilities\Request;
use JiFramework\Tests\TestCase;

class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR']      = '203.0.113.5';
        $_SERVER['REQUEST_METHOD']   = 'GET';
        $_SERVER['REQUEST_URI']      = '/test?foo=bar';
        $_SERVER['HTTP_HOST']        = 'example.com';
        $_SERVER['SERVER_SOFTWARE']  = 'Apache/2.4';
        $_SERVER['SERVER_PROTOCOL']  = 'HTTP/1.1';
        $_SERVER['DOCUMENT_ROOT']    = '/var/www/html';
        $_SERVER['HTTPS']            = 'off';
        $_SERVER['SERVER_PORT']      = '80';

        $this->request = new Request();
    }

    // ── getClientIp() ────────────────────────────────────────────────────────

    public function testGetClientIpReturnsRemoteAddr(): void
    {
        $ip = $this->request->getClientIp();
        $this->assertSame('203.0.113.5', $ip);
    }

    public function testGetClientIpIgnoresForwardedHeaderWithoutTrustedProxy(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $req = new Request();
        // Without trusted proxies configured, REMOTE_ADDR is authoritative
        $this->assertSame('203.0.113.5', $req->getClientIp());
    }

    // ── getRequestMethod() ───────────────────────────────────────────────────

    public function testGetRequestMethod(): void
    {
        $this->assertSame('GET', $this->request->getRequestMethod());
    }

    // ── getServerInfo() ──────────────────────────────────────────────────────

    public function testGetServerInfo(): void
    {
        $info = $this->request->getServerInfo();
        $this->assertIsArray($info);
        $this->assertArrayHasKey('server_software', $info);
        $this->assertArrayHasKey('server_protocol', $info);
        $this->assertArrayHasKey('document_root', $info);
        $this->assertSame('Apache/2.4', $info['server_software']);
    }

    // ── getPhpVersion() ──────────────────────────────────────────────────────

    public function testGetPhpVersion(): void
    {
        $version = $this->request->getPhpVersion();
        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    // ── isHttps() ────────────────────────────────────────────────────────────

    public function testIsHttpsFalseByDefault(): void
    {
        $this->assertFalse($this->request->isHttps());
    }

    public function testIsHttpsTrueWhenHttpsSet(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $req = new Request();
        $this->assertTrue($req->isHttps());
        $_SERVER['HTTPS'] = 'off';
    }

    // ── isAjax() ─────────────────────────────────────────────────────────────

    public function testIsAjaxFalseWithoutHeader(): void
    {
        $this->assertFalse($this->request->isAjax());
    }

    public function testIsAjaxTrueWithXmlHttpRequestHeader(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $req = new Request();
        $this->assertTrue($req->isAjax());
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    // ── isCli() ──────────────────────────────────────────────────────────────

    public function testIsCliTrueInTestEnvironment(): void
    {
        // Tests run in CLI
        $this->assertTrue($this->request->isCli());
    }

    // ── getBody() ─────────────────────────────────────────────────────────────

    public function testGetBodyReturnsString(): void
    {
        $body = $this->request->getBody();
        $this->assertIsString($body);
    }

    // ── getBearerToken() ─────────────────────────────────────────────────────

    public function testGetBearerTokenReturnsNull(): void
    {
        $this->assertNull($this->request->getBearerToken());
    }

    public function testGetBearerTokenExtractsToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer mytoken123';
        $req = new Request();
        $this->assertSame('mytoken123', $req->getBearerToken());
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    // ── getEnv() ─────────────────────────────────────────────────────────────

    public function testGetEnvReturnsDefault(): void
    {
        $result = $this->request->getEnv('TOTALLY_NONEXISTENT_VAR_XYZ', 'fallback');
        $this->assertSame('fallback', $result);
    }

    // ── getRequestHeaders() ──────────────────────────────────────────────────

    public function testGetRequestHeadersReturnsArray(): void
    {
        $headers = $this->request->getRequestHeaders();
        $this->assertIsArray($headers);
    }
}
