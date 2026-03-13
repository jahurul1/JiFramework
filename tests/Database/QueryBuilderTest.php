<?php

namespace JiFramework\Tests\Database;

use JiFramework\Core\Database\DatabaseConnection;
use JiFramework\Core\Database\QueryBuilder;
use JiFramework\Tests\TestCase;
use PDO;
use ReflectionClass;

/**
 * QueryBuilder tests use an in-memory SQLite database.
 *
 * Because DatabaseConnection hardcodes a MySQL DSN, we inject the SQLite PDO
 * directly into its connections cache via Reflection before creating any QueryBuilder.
 */
class QueryBuilderTest extends TestCase
{
    private PDO $pdo;
    private QueryBuilder $qb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = $this->buildSqlite();
        $this->qb  = new QueryBuilder('primary');
    }

    private function buildSqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Inject into DatabaseConnection so any new QueryBuilder('primary') uses this PDO
        $ref  = new ReflectionClass(DatabaseConnection::class);
        $prop = $ref->getProperty('connections');
        $prop->setAccessible(true);
        $prop->setValue(null, ['primary' => $pdo]);

        $pdo->exec("
            CREATE TABLE users (
                id      INTEGER PRIMARY KEY AUTOINCREMENT,
                name    TEXT    NOT NULL,
                email   TEXT    NOT NULL,
                age     INTEGER DEFAULT 0,
                active  INTEGER DEFAULT 1
            )
        ");
        $pdo->exec("
            INSERT INTO users (name, email, age, active) VALUES
                ('Alice', 'alice@test.com', 30, 1),
                ('Bob',   'bob@test.com',   25, 1),
                ('Carol', 'carol@test.com', 35, 0)
        ");

        return $pdo;
    }

    protected function tearDown(): void
    {
        // Clear the connection cache so subsequent test files start fresh
        $ref  = new ReflectionClass(DatabaseConnection::class);
        $prop = $ref->getProperty('connections');
        $prop->setAccessible(true);
        $prop->setValue(null, []);

        parent::tearDown();
    }

    // ── table() / get() ──────────────────────────────────────────────────────

    public function testGetAllRows(): void
    {
        $rows = $this->qb->table('users')->get();
        $this->assertCount(3, $rows);
    }

    // ── select() ─────────────────────────────────────────────────────────────

    public function testSelectSpecificColumns(): void
    {
        $rows = $this->qb->table('users')->select(['name', 'email'])->get();
        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertArrayHasKey('email', $rows[0]);
        $this->assertArrayNotHasKey('id', $rows[0]);
    }

    // ── where() ──────────────────────────────────────────────────────────────

    public function testWhereEquals(): void
    {
        $rows = $this->qb->table('users')->where('name', '=', 'Alice')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    public function testWhereActive(): void
    {
        $rows = $this->qb->table('users')->where('active', '=', 1)->get();
        $this->assertCount(2, $rows);
    }

    public function testWhereChained(): void
    {
        $rows = $this->qb->table('users')
            ->where('active', '=', 1)
            ->where('age', '>', 26)
            ->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    // ── orWhere() ────────────────────────────────────────────────────────────

    public function testOrWhere(): void
    {
        $rows = $this->qb->table('users')
            ->where('name', '=', 'Alice')
            ->orWhere('name', '=', 'Bob')
            ->get();
        $this->assertCount(2, $rows);
    }

    // ── first() ──────────────────────────────────────────────────────────────

    public function testFirst(): void
    {
        $row = $this->qb->table('users')->where('name', '=', 'Bob')->first();
        $this->assertIsArray($row);
        $this->assertSame('Bob', $row['name']);
    }

    public function testFirstReturnsNullWhenNoMatch(): void
    {
        $row = $this->qb->table('users')->where('name', '=', 'NoOne')->first();
        $this->assertNull($row);
    }

    // ── count() ──────────────────────────────────────────────────────────────

    public function testCount(): void
    {
        $this->assertSame(3, $this->qb->table('users')->count());
        $this->assertSame(1, $this->qb->table('users')->where('active', '=', 0)->count());
    }

    // ── orderBy() / limit() / offset() ───────────────────────────────────────

    public function testOrderByAsc(): void
    {
        $rows = $this->qb->table('users')->orderBy('age', 'ASC')->get();
        $this->assertSame('Bob', $rows[0]['name']);
    }

    public function testOrderByDesc(): void
    {
        $rows = $this->qb->table('users')->orderBy('age', 'DESC')->get();
        $this->assertSame('Carol', $rows[0]['name']);
    }

    public function testLimit(): void
    {
        $rows = $this->qb->table('users')->limit(2)->get();
        $this->assertCount(2, $rows);
    }

    public function testOffset(): void
    {
        $rows = $this->qb->table('users')->orderBy('id', 'ASC')->offset(1)->limit(2)->get();
        $this->assertCount(2, $rows);
        $this->assertSame('Bob', $rows[0]['name']);
    }

    // ── insert() ─────────────────────────────────────────────────────────────

    public function testInsert(): void
    {
        // insert() returns bool; use insertGetId() to retrieve the last insert ID
        $result = $this->qb->table('users')->insert([
            'name'  => 'Dave',
            'email' => 'dave@test.com',
            'age'   => 22,
        ]);
        $this->assertTrue($result);
        $this->assertSame(4, $this->qb->table('users')->count());
    }

    public function testInsertGetId(): void
    {
        $id = $this->qb->table('users')->insertGetId([
            'name'  => 'Eve',
            'email' => 'eve@test.com',
            'age'   => 28,
        ]);
        $this->assertIsInt((int) $id);
        $this->assertGreaterThan(0, (int) $id);

        $row = $this->qb->table('users')->where('name', '=', 'Eve')->first();
        $this->assertSame('Eve', $row['name']);
    }

    // ── update() ─────────────────────────────────────────────────────────────

    public function testUpdate(): void
    {
        $affected = $this->qb->table('users')
            ->where('name', '=', 'Bob')
            ->update(['age' => 99]);
        $this->assertGreaterThan(0, $affected);

        $row = $this->qb->table('users')->where('name', '=', 'Bob')->first();
        $this->assertSame(99, (int) $row['age']);
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDelete(): void
    {
        $affected = $this->qb->table('users')->where('name', '=', 'Carol')->delete();
        $this->assertGreaterThan(0, $affected);
        $this->assertSame(2, $this->qb->table('users')->count());
    }

    // ── whereIn() ────────────────────────────────────────────────────────────

    public function testWhereIn(): void
    {
        $rows = $this->qb->table('users')->whereIn('name', ['Alice', 'Bob'])->get();
        $this->assertCount(2, $rows);
    }

    // ── whereBetween() ───────────────────────────────────────────────────────

    public function testWhereBetween(): void
    {
        $rows = $this->qb->table('users')->whereBetween('age', 28, 36)->get();
        $this->assertCount(2, $rows); // Alice (30) and Carol (35)
    }

    // ── paginate() ───────────────────────────────────────────────────────────

    public function testPaginate(): void
    {
        $result = $this->qb->table('users')->orderBy('id', 'ASC')->paginate(2, 1);
        $this->assertIsObject($result);
        $this->assertSame(3, $result->totalItems);
        $this->assertSame(2, $result->totalPages);
        $this->assertCount(2, $result->data);
    }

    // ── selectRaw() ───────────────────────────────────────────────────────────

    public function testSelectRaw(): void
    {
        $rows = $this->qb->table('users')->selectRaw('COUNT(*) as cnt')->get();
        $this->assertSame(3, (int) $rows[0]['cnt']);
    }
}
