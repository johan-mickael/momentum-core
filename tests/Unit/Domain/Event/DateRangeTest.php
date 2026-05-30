<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Event\Exception\InvalidDateRange;
use App\Domain\Event\ValueObject\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testCreateWithValidRange(): void
    {
        $start = new \DateTimeImmutable('2026-06-01 10:00:00');
        $end = new \DateTimeImmutable('2026-06-01 12:00:00');

        $range = DateRange::create($start, $end);

        self::assertSame($start, $range->startAt);
        self::assertSame($end, $range->endAt);
    }

    public function testCreateWithEndBeforeStartThrows(): void
    {
        $this->expectException(InvalidDateRange::class);

        DateRange::create(
            new \DateTimeImmutable('2026-06-01 12:00:00'),
            new \DateTimeImmutable('2026-06-01 10:00:00'),
        );
    }

    public function testCreateWithEndEqualToStartThrows(): void
    {
        $this->expectException(InvalidDateRange::class);

        $moment = new \DateTimeImmutable('2026-06-01 10:00:00');
        DateRange::create($moment, $moment);
    }

    public function testIsOngoingReturnsTrueWhenNowIsWithinRange(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2026-06-01 09:00:00'),
            new \DateTimeImmutable('2026-06-01 11:00:00'),
        );
        $now = new \DateTimeImmutable('2026-06-01 10:00:00');

        self::assertTrue($range->isOngoing($now));
    }

    public function testIsOngoingReturnsFalseWhenNowIsBeforeStart(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2026-06-01 10:00:00'),
            new \DateTimeImmutable('2026-06-01 12:00:00'),
        );
        $now = new \DateTimeImmutable('2026-06-01 09:00:00');

        self::assertFalse($range->isOngoing($now));
    }

    public function testIsOngoingReturnsFalseWhenNowIsAfterEnd(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2026-06-01 10:00:00'),
            new \DateTimeImmutable('2026-06-01 12:00:00'),
        );
        $now = new \DateTimeImmutable('2026-06-01 13:00:00');

        self::assertFalse($range->isOngoing($now));
    }

    public function testIsInFutureReturnsTrueWhenStartIsInFuture(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2040-01-01 10:00:00'),
            new \DateTimeImmutable('2040-01-01 12:00:00'),
        );

        self::assertTrue($range->isInFuture());
    }

    public function testIsInFutureReturnsFalseWhenStartIsInPast(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2020-01-01 10:00:00'),
            new \DateTimeImmutable('2020-01-01 12:00:00'),
        );

        self::assertFalse($range->isInFuture());
    }

    public function testIsPastReturnsTrueWhenEndIsInPast(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2020-01-01 10:00:00'),
            new \DateTimeImmutable('2020-01-01 12:00:00'),
        );

        self::assertTrue($range->isPast());
    }

    public function testIsPastReturnsFalseWhenEndIsInFuture(): void
    {
        $range = DateRange::create(
            new \DateTimeImmutable('2040-01-01 10:00:00'),
            new \DateTimeImmutable('2040-01-01 12:00:00'),
        );

        self::assertFalse($range->isPast());
    }

    public function testIsOngoingWithExplicitNowAtStartBoundary(): void
    {
        $start = new \DateTimeImmutable('2026-06-01 10:00:00');
        $end = new \DateTimeImmutable('2026-06-01 12:00:00');
        $range = DateRange::create($start, $end);

        self::assertTrue($range->isOngoing($start));
    }

    public function testIsOngoingWithExplicitNowAtEndBoundary(): void
    {
        $start = new \DateTimeImmutable('2026-06-01 10:00:00');
        $end = new \DateTimeImmutable('2026-06-01 12:00:00');
        $range = DateRange::create($start, $end);

        self::assertTrue($range->isOngoing($end));
    }
}
