<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Venue;

use Domain\Venue\Exception\InvalidVenueCapacity;
use Domain\Venue\ValueObject\Capacity;
use PHPUnit\Framework\TestCase;

final class CapacityTest extends TestCase
{
    public function testFromIntegerWithValidValue(): void
    {
        $capacity = Capacity::fromInteger(500);

        self::assertSame(500, $capacity->value);
    }

    public function testFromIntegerWithMinimumAllowedValue(): void
    {
        $capacity = Capacity::fromInteger(1);

        self::assertSame(1, $capacity->value);
    }

    public function testFromIntegerWithZeroThrows(): void
    {
        $this->expectException(InvalidVenueCapacity::class);

        Capacity::fromInteger(0);
    }

    public function testFromIntegerWithNegativeValueThrows(): void
    {
        $this->expectException(InvalidVenueCapacity::class);

        Capacity::fromInteger(-1);
    }

    public function testFromStringWithValidValue(): void
    {
        $capacity = Capacity::fromString('250');

        self::assertSame(250, $capacity->value);
    }

    public function testFromStringWithZeroThrows(): void
    {
        $this->expectException(InvalidVenueCapacity::class);

        Capacity::fromString('0');
    }
}
