<?php

namespace JiFramework\Tests\Database;

use JiFramework\Config\Config;
use JiFramework\Core\App\App;
use JiFramework\Core\Database\DatabaseConnection;
use JiFramework\Core\Database\Model;
use JiFramework\Tests\TestCase;
use PDO;
use ReflectionClass;

/**
 * Model tests use an in-memory SQLite database.
 * The SQLite PDO is injected directly into DatabaseConnection's connection cache.
 */
class ModelTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applyTestConfig();

        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';

        // Build in-memory SQLite and inject into DatabaseConnection BEFORE App boots
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $ref  = new ReflectionClass(DatabaseConnection::class);
        $prop = $ref->getProperty('connections');
        $prop->setAccessible(true);
        $prop->setValue(null, ['primary' => $pdo]);

        $this->app = new App();

        // Create the test table via raw SQL
        $pdo->exec("
            CREATE TABLE test_products (
                id     INTEGER PRIMARY KEY AUTOINCREMENT,
                name   TEXT NOT NULL,
                price  REAL DEFAULT 0,
                active INTEGER DEFAULT 1
            )
        ");
        $pdo->exec("
            INSERT INTO test_products (name, price, active) VALUES
                ('Widget', 9.99,  1),
                ('Gadget', 19.99, 1),
                ('Donut',  1.99,  0)
        ");
    }

    protected function tearDown(): void
    {
        $ref  = new ReflectionClass(DatabaseConnection::class);
        $prop = $ref->getProperty('connections');
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        parent::tearDown();
    }

    // ── all() ─────────────────────────────────────────────────────────────────

    public function testAllReturnsAllRows(): void
    {
        $rows = TestProduct::all();
        $this->assertCount(3, $rows);
    }

    // ── find() ───────────────────────────────────────────────────────────────

    public function testFindById(): void
    {
        $row = TestProduct::find(1);
        $this->assertIsArray($row);
        $this->assertSame('Widget', $row['name']);
    }

    public function testFindReturnsNullForMissingId(): void
    {
        $this->assertNull(TestProduct::find(999));
    }

    // ── where() ──────────────────────────────────────────────────────────────

    public function testWhereReturnsQueryBuilder(): void
    {
        $rows = TestProduct::where('active', '=', 1)->get();
        $this->assertCount(2, $rows);
    }

    // ── first() ──────────────────────────────────────────────────────────────

    public function testFirst(): void
    {
        $row = TestProduct::first();
        $this->assertIsArray($row);
        $this->assertArrayHasKey('name', $row);
    }

    // ── count() ──────────────────────────────────────────────────────────────

    public function testCount(): void
    {
        $this->assertSame(3, TestProduct::count());
    }

    // ── exists($id) — checks by primary key ──────────────────────────────────

    public function testExistsTrue(): void
    {
        $this->assertTrue(TestProduct::exists(1)); // id=1 exists
    }

    public function testExistsFalse(): void
    {
        $this->assertFalse(TestProduct::exists(999)); // id=999 doesn't exist
    }

    // ── insert() — returns bool ───────────────────────────────────────────────

    public function testInsert(): void
    {
        $result = TestProduct::insert(['name' => 'Thingamajig', 'price' => 5.00]);
        $this->assertTrue($result);
        $this->assertSame(4, TestProduct::count());
    }

    // ── create() — returns int (last insert ID) ───────────────────────────────

    public function testCreate(): void
    {
        $id  = TestProduct::create(['name' => 'Whatchamacallit', 'price' => 3.50]);
        $this->assertIsInt($id);
        $row = TestProduct::find($id);
        $this->assertSame('Whatchamacallit', $row['name']);
    }

    // ── update($data, $id) — data first, id second ────────────────────────────

    public function testUpdate(): void
    {
        $result = TestProduct::update(['price' => 99.99], 1);
        $this->assertTrue((bool) $result);

        $row = TestProduct::find(1);
        $this->assertEqualsWithDelta(99.99, (float) $row['price'], 0.001);
    }

    // ── destroy() ────────────────────────────────────────────────────────────

    public function testDestroy(): void
    {
        $affected = TestProduct::destroy(3);
        $this->assertGreaterThan(0, $affected);
        $this->assertSame(2, TestProduct::count());
        $this->assertNull(TestProduct::find(3));
    }

    // ── Chaining ─────────────────────────────────────────────────────────────

    public function testChainWhereAndOrderBy(): void
    {
        $rows = TestProduct::where('active', '=', 1)->orderBy('price', 'DESC')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('Gadget', $rows[0]['name']);
    }
}

// ── Inline test model ─────────────────────────────────────────────────────────

class TestProduct extends Model
{
    protected static string $table      = 'test_products';
    protected static string $primaryKey = 'id';
    protected static string $connection = 'primary';
}
