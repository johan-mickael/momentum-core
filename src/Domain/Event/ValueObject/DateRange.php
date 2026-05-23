<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

use App\Domain\Event\Exception\InvalidDateRange;
use DateTimeImmutable;

final readonly class DateRange
{
    private function __construct(
        public DateTimeImmutable $startAt,
        public DateTimeImmutable $endAt,
    ) {
    }

    public static function create(
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
    ): DateRange {
        return new self($startAt, $endAt)->assert();
    }

    public static function reconstitute(
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
    ): self {
        return new self($startAt, $endAt);
    }

    private function assert(): self
    {
        if ($this->endAt <= $this->startAt) {
            throw InvalidDateRange::endBeforeStart(
                $this->startAt,
                $this->endAt
            );
        }

        return $this;
    }

    public function isOngoing(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $now >= $this->startAt && $now <= $this->endAt;
    }

    public function isInFuture(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $this->startAt > $now;
    }

    public function isPast(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $this->endAt < $now;
    }
}
