<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Common\ValueObject;

use App\Domain\Common\Exception\InvalidPageLimit;
use App\Domain\Common\Exception\InvalidPageNumber;
use App\Domain\Common\ValueObject\PaginationCriteria;
use PHPUnit\Framework\TestCase;

final class PaginationCriteriaTest extends TestCase
{
    public function testValidCriteriaCreatesSuccessfully(): void
    {
        $criteria = new PaginationCriteria(3, 50);

        self::assertSame(3, $criteria->page);
        self::assertSame(50, $criteria->limit);
    }

    public function testMinimumValidValues(): void
    {
        $criteria = new PaginationCriteria(1, 1);

        self::assertSame(1, $criteria->page);
        self::assertSame(1, $criteria->limit);
    }

    public function testMaximumValidLimit(): void
    {
        $criteria = new PaginationCriteria(1, PaginationCriteria::MAX_LIMIT);

        self::assertSame(PaginationCriteria::MAX_LIMIT, $criteria->limit);
    }

    public function testDefaultCreatesPageOneWithDefaultLimit(): void
    {
        $criteria = PaginationCriteria::default();

        self::assertSame(PaginationCriteria::DEFAULT_PAGE, $criteria->page);
        self::assertSame(PaginationCriteria::DEFAULT_LIMIT, $criteria->limit);
    }

    public function testOffsetOnFirstPage(): void
    {
        $criteria = new PaginationCriteria(1, 20);

        self::assertSame(0, $criteria->offset());
    }

    public function testOffsetOnSubsequentPages(): void
    {
        $criteria = new PaginationCriteria(3, 20);

        self::assertSame(40, $criteria->offset());
    }

    public function testOffsetWithCustomLimit(): void
    {
        $criteria = new PaginationCriteria(4, 10);

        self::assertSame(30, $criteria->offset());
    }

    public function testPageZeroThrows(): void
    {
        $this->expectException(InvalidPageNumber::class);

        new PaginationCriteria(0, 20);
    }

    public function testNegativePageThrows(): void
    {
        $this->expectException(InvalidPageNumber::class);

        new PaginationCriteria(-1, 20);
    }

    public function testLimitZeroThrows(): void
    {
        $this->expectException(InvalidPageLimit::class);

        new PaginationCriteria(1, 0);
    }

    public function testNegativeLimitThrows(): void
    {
        $this->expectException(InvalidPageLimit::class);

        new PaginationCriteria(1, -5);
    }

    public function testLimitExceedsMaxThrows(): void
    {
        $this->expectException(InvalidPageLimit::class);

        new PaginationCriteria(1, PaginationCriteria::MAX_LIMIT + 1);
    }
}
