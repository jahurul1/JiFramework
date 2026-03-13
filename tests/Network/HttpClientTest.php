<?php

namespace JiFramework\Tests\Network;

use JiFramework\Core\Network\HttpClient;
use JiFramework\Tests\TestCase;

/**
 * HttpClient tests make real HTTP requests.
 *
 * These tests are excluded from the default test run.
 * Run them explicitly with: vendor/bin/phpunit --group network
 *
 * @group network
 */
class HttpClientTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new HttpClient();
    }

    // ── GET ──────────────────────────────────────────────────────────────────

    public function testGetReturnsExpectedKeys(): void
    {
        $response = $this->client->get('https://httpbin.org/get');
        $this->assertArrayHasKey('status_code', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('error', $response);
    }

    public function testGetSuccess(): void
    {
        $response = $this->client->get('https://httpbin.org/get');
        $this->assertSame(200, $response['status_code']);
        $this->assertNull($response['error']);
    }

    public function testGetWithQueryParams(): void
    {
        $response = $this->client->get('https://httpbin.org/get?foo=bar');
        $this->assertSame(200, $response['status_code']);
        $body = json_decode($response['body'], true);
        $this->assertSame('bar', $body['args']['foo'] ?? null);
    }

    // ── POST ─────────────────────────────────────────────────────────────────

    public function testPostFormData(): void
    {
        $response = $this->client->post('https://httpbin.org/post', ['key' => 'value']);
        $this->assertSame(200, $response['status_code']);
    }

    public function testPostJson(): void
    {
        $response = $this->client->post(
            'https://httpbin.org/post',
            ['name' => 'Alice'],
            ['json' => true]
        );
        $this->assertSame(200, $response['status_code']);
        $body = json_decode($response['body'], true);
        $this->assertSame('Alice', $body['json']['name'] ?? null);
    }

    // ── Status codes ─────────────────────────────────────────────────────────

    public function testGet404(): void
    {
        $response = $this->client->get('https://httpbin.org/status/404');
        $this->assertSame(404, $response['status_code']);
    }

    public function testGet500(): void
    {
        $response = $this->client->get('https://httpbin.org/status/500');
        $this->assertSame(500, $response['status_code']);
    }

    // ── Custom headers ────────────────────────────────────────────────────────

    public function testCustomHeaders(): void
    {
        $response = $this->client->get('https://httpbin.org/headers', [
            'headers' => ['X-Custom-Header' => 'TestValue'],
        ]);
        $this->assertSame(200, $response['status_code']);
        $body = json_decode($response['body'], true);
        $this->assertSame('TestValue', $body['headers']['X-Custom-Header'] ?? null);
    }

    // ── Timeout ──────────────────────────────────────────────────────────────

    public function testTimeoutOption(): void
    {
        $response = $this->client->get('https://httpbin.org/get', ['timeout' => 10]);
        $this->assertSame(200, $response['status_code']);
    }

    // ── Invalid URL ───────────────────────────────────────────────────────────

    public function testInvalidUrlReturnsError(): void
    {
        $response = $this->client->get('http://this-domain-does-not-exist-xyz-abc.invalid/');
        $this->assertNotNull($response['error']);
    }

    // ── PUT / DELETE ──────────────────────────────────────────────────────────

    public function testPut(): void
    {
        $response = $this->client->put('https://httpbin.org/put', ['updated' => true]);
        $this->assertSame(200, $response['status_code']);
    }

    public function testDelete(): void
    {
        $response = $this->client->delete('https://httpbin.org/delete');
        $this->assertSame(200, $response['status_code']);
    }

    // ── PATCH ────────────────────────────────────────────────────────────────

    public function testPatch(): void
    {
        $response = $this->client->patch('https://httpbin.org/patch', ['field' => 'new']);
        $this->assertSame(200, $response['status_code']);
    }
}
