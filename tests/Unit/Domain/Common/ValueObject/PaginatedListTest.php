<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObject;

use Domain\Common\ValueObject\PaginatedList;
use PHPUnit\Framework\TestCase;

final class PaginatedListTest extends TestCase
{
    public function testTotalPagesWithExactDivision(): void
    {
        $list = new PaginatedList([], 100, 1, 20);

        self::assertSame(5, $list->totalPages());
    }

    public function testTotalPagesWithRemainder(): void
    {
        $list = new PaginatedList([], 101, 1, 20);

        self::assertSame(6, $list->totalPages());
    }

    public function testTotalPagesWhenEmptyResultSet(): void
    {
        $list = new PaginatedList([], 0, 1, 20);

        self::assertSame(0, $list->totalPages());
    }

    public function testTotalPagesWhenResultsFitInOnePage(): void
    {
        $list = new PaginatedList(['a', 'b'], 2, 1, 20);

        self::assertSame(1, $list->totalPages());
    }

    public function testItemsAndMetadataStoredCorrectly(): void
    {
        $items = ['x', 'y', 'z'];
        $list = new PaginatedList($items, 42, 3, 10);

        self::assertSame($items, $list->items);
        self::assertSame(42, $list->total);
        self::assertSame(3, $list->page);
        self::assertSame(10, $list->limit);
    }
}
