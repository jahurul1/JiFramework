<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Config\Config;
use JiFramework\Core\Session\SessionManager;
use JiFramework\Tests\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class SessionManagerTest extends TestCase
{
    private SessionManager $sm;

    protected function setUp(): void
    {
        parent::setUp();

        Config::initialize();
        Config::$rateLimitEnabled       = false;
        Config::$ipBlockingEnabled      = false;
        Config::$countryBlockingEnabled = false;
        Config::$logEnabled             = false;

        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->sm = new SessionManager();
    }

    // ── isStarted() ──────────────────────────────────────────────────────────

    public function testIsStartedAfterBoot(): void
    {
        $this->assertTrue($this->sm->isStarted());
    }

    // ── set() / get() ─────────────────────────────────────────────────────────

    public function testSetAndGet(): void
    {
        $this->sm->set('name', 'Alice');
        $this->assertSame('Alice', $this->sm->get('name'));
    }

    public function testGetDefaultForMissingKey(): void
    {
        $this->assertNull($this->sm->get('nonexistent_xyz'));
        $this->assertSame('default', $this->sm->get('nonexistent_xyz', 'default'));
    }

    public function testSetArray(): void
    {
        $this->sm->set('prefs', ['theme' => 'dark']);
        $this->assertSame(['theme' => 'dark'], $this->sm->get('prefs'));
    }

    // ── has() ────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->sm->set('x', 1);
        $this->assertTrue($this->sm->has('x'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->sm->has('not_set_xyz_abc'));
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDelete(): void
    {
        $this->sm->set('to_remove', 'bye');
        $this->sm->delete('to_remove');
        $this->assertFalse($this->sm->has('to_remove'));
    }

    // ── clear() ──────────────────────────────────────────────────────────────

    public function testClear(): void
    {
        $this->sm->set('a', 1);
        $this->sm->set('b', 2);
        $this->sm->clear();
        $this->assertFalse($this->sm->has('a'));
        $this->assertFalse($this->sm->has('b'));
    }

    // ── all() ────────────────────────────────────────────────────────────────

    public function testAll(): void
    {
        $this->sm->clear();
        $this->sm->set('key1', 'val1');
        $all = $this->sm->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('key1', $all);
    }

    // ── Flash messages ────────────────────────────────────────────────────────
    // setFlashMessage(string $type, string $message, array $data = [])
    // getFlashMessages(): array

    public function testSetAndGetFlashMessages(): void
    {
        $this->sm->setFlashMessage('success', 'Saved!');
        $messages = $this->sm->getFlashMessages();
        $this->assertNotEmpty($messages);
        $found = false;
        foreach ($messages as $msg) {
            if ($msg['type'] === 'success' && $msg['message'] === 'Saved!') {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Flash message not found in getFlashMessages()');
    }

    public function testFlashConvenienceMethods(): void
    {
        $this->sm->flashSuccess('Done');
        $this->sm->flashError('Failed');
        $messages = $this->sm->getFlashMessages();
        $types = array_column($messages, 'type');
        $this->assertContains('success', $types);
        $this->assertContains('error', $types);
    }

    // ── CSRF token ───────────────────────────────────────────────────────────

    public function testGenerateCsrfTokenReturnsString(): void
    {
        $token = $this->sm->generateCsrfToken();
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testVerifyCsrfToken(): void
    {
        $token = $this->sm->generateCsrfToken();
        $_POST['_csrf_token'] = $token; // verifyCsrfToken reads from $_POST
        $this->assertTrue($this->sm->verifyCsrfToken($token));
    }

    public function testInvalidCsrfTokenFails(): void
    {
        $this->sm->generateCsrfToken();
        $this->assertFalse($this->sm->verifyCsrfToken('wrong-token'));
    }

    // ── id() ─────────────────────────────────────────────────────────────────

    public function testIdReturnsString(): void
    {
        $id = $this->sm->id();
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }
}
