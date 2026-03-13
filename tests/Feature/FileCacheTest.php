<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Core\Cache\FileCache;
use JiFramework\Tests\TestCase;

class FileCacheTest extends TestCase
{
    private FileCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new FileCache($this->tempDir . 'cache/');
    }

    // ── set() / get() ─────────────────────────────────────────────────────────

    public function testSetAndGetString(): void
    {
        $this->cache->set('greeting', 'hello', 60);
        $this->assertSame('hello', $this->cache->get('greeting'));
    }

    public function testSetAndGetArray(): void
    {
        $data = ['a' => 1, 'b' => 2];
        $this->cache->set('arr', $data, 60);
        $this->assertSame($data, $this->cache->get('arr'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testCacheExpiry(): void
    {
        $this->cache->set('short', 'value', 1); // 1 second TTL
        $this->assertSame('value', $this->cache->get('short'));
        sleep(2);
        $this->assertNull($this->cache->get('short'));
    }

    // ── has() ─────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->cache->set('key', 'val', 60);
        $this->assertTrue($this->cache->has('key'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->cache->has('missing'));
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDelete(): void
    {
        $this->cache->set('to_delete', 'bye', 60);
        $this->cache->delete('to_delete');
        $this->assertNull($this->cache->get('to_delete'));
        $this->assertFalse($this->cache->has('to_delete'));
    }

    // ── clear() ──────────────────────────────────────────────────────────────

    public function testClearRemovesAllEntries(): void
    {
        $this->cache->set('a', 1, 60);
        $this->cache->set('b', 2, 60);
        $this->cache->clear();
        $this->assertNull($this->cache->get('a'));
        $this->assertNull($this->cache->get('b'));
    }

    // ── increment() / decrement() ─────────────────────────────────────────────

    public function testIncrement(): void
    {
        $this->cache->set('counter', 5, 60);
        $this->cache->increment('counter', 3);
        $this->assertSame(8, $this->cache->get('counter'));
    }

    public function testDecrement(): void
    {
        $this->cache->set('counter', 10, 60);
        $this->cache->decrement('counter', 4);
        $this->assertSame(6, $this->cache->get('counter'));
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function testSetOverwritesExistingKey(): void
    {
        $this->cache->set('k', 'old', 60);
        $this->cache->set('k', 'new', 60);
        $this->assertSame('new', $this->cache->get('k'));
    }
}
