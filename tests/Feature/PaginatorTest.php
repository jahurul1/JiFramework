<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Core\Utilities\Paginator;
use JiFramework\Tests\TestCase;

class PaginatorTest extends TestCase
{
    private Paginator $paginator;

    protected function setUp(): void
    {
        parent::setUp();
        $_GET = []; // reset query string
        $this->paginator = new Paginator();
    }

    // ── paginate() core math ──────────────────────────────────────────────────

    public function testFirstPage(): void
    {
        $data = $this->paginator->paginate(10, 100, ['currentPage' => 1]);
        $this->assertSame(1, $data->currentPage);
        $this->assertSame(10, $data->totalPages);
        $this->assertSame(100, $data->totalItems);
        $this->assertSame(10, $data->perPage);
        $this->assertSame(0, $data->offset);
        $this->assertTrue($data->hasNext);
        $this->assertFalse($data->hasPrevious);
        $this->assertSame(2, $data->nextPage);
        $this->assertSame(1, $data->previousPage);
    }

    public function testLastPage(): void
    {
        $data = $this->paginator->paginate(10, 100, ['currentPage' => 10]);
        $this->assertSame(10, $data->currentPage);
        $this->assertFalse($data->hasNext);
        $this->assertTrue($data->hasPrevious);
        $this->assertSame(90, $data->offset);
    }

    public function testMiddlePage(): void
    {
        $data = $this->paginator->paginate(10, 100, ['currentPage' => 5]);
        $this->assertSame(5, $data->currentPage);
        $this->assertTrue($data->hasNext);
        $this->assertTrue($data->hasPrevious);
        $this->assertSame(40, $data->offset);
        $this->assertSame(6, $data->nextPage);
        $this->assertSame(4, $data->previousPage);
    }

    public function testSinglePage(): void
    {
        $data = $this->paginator->paginate(10, 5, ['currentPage' => 1]);
        $this->assertSame(1, $data->totalPages);
        $this->assertFalse($data->hasNext);
        $this->assertFalse($data->hasPrevious);
    }

    public function testZeroItems(): void
    {
        $data = $this->paginator->paginate(10, 0, ['currentPage' => 1]);
        $this->assertSame(1, $data->totalPages);
        $this->assertSame(0, $data->totalItems);
        $this->assertFalse($data->hasNext);
    }

    public function testPageClampedToTotalPages(): void
    {
        $data = $this->paginator->paginate(10, 30, ['currentPage' => 999]);
        $this->assertSame(3, $data->currentPage); // clamped to last page
    }

    public function testPageClampedToOne(): void
    {
        $data = $this->paginator->paginate(10, 50, ['currentPage' => -5]);
        $this->assertSame(1, $data->currentPage);
    }

    public function testPerPageClampedToOne(): void
    {
        $data = $this->paginator->paginate(0, 50, ['currentPage' => 1]);
        $this->assertSame(1, $data->perPage);
    }

    // ── Offset calculation ────────────────────────────────────────────────────

    public function testOffsetCalculation(): void
    {
        $data = $this->paginator->paginate(15, 100, ['currentPage' => 3]);
        $this->assertSame(30, $data->offset); // (3-1) * 15
    }

    // ── queryParams carry-through ─────────────────────────────────────────────

    public function testQueryParamsCarriedThrough(): void
    {
        $_GET = ['search' => 'php', 'sort' => 'asc'];
        $data = $this->paginator->paginate(10, 50, ['currentPage' => 1]);
        $this->assertStringContainsString('search=php', $data->queryParams);
        $this->assertStringNotContainsString('page=', $data->queryParams);
    }

    // ── $_GET page detection ──────────────────────────────────────────────────

    public function testCurrentPageFromGetParam(): void
    {
        $_GET = ['page' => '3'];
        $data = $this->paginator->paginate(10, 100);
        $this->assertSame(3, $data->currentPage);
        $_GET = [];
    }
}
