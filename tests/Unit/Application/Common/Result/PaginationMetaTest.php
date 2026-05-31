<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Common\Result;

use Application\Common\Result\PaginationMeta;
use PHPUnit\Framework\TestCase;

final class PaginationMetaTest extends TestCase
{
    public function testTotalPagesComputedCorrectly(): void
    {
        $meta = new PaginationMeta(page: 1, limit: 20, total: 100);

        self::assertSame(5, $meta->totalPages);
    }

    public function testTotalPagesRoundsUp(): void
    {
        $meta = new PaginationMeta(page: 2, limit: 20, total: 41);

        self::assertSame(3, $meta->totalPages);
    }

    public function testTotalPagesZeroWhenNoResults(): void
    {
        $meta = new PaginationMeta(page: 1, limit: 20, total: 0);

        self::assertSame(0, $meta->totalPages);
    }

    public function testPropertiesExposedCorrectly(): void
    {
        $meta = new PaginationMeta(page: 2, limit: 15, total: 45);

        self::assertSame(2, $meta->page);
        self::assertSame(15, $meta->limit);
        self::assertSame(45, $meta->total);
        self::assertSame(3, $meta->totalPages);
    }
}
