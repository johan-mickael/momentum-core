<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Venue;

use App\Domain\Venue\Exception\InvalidVenueId;
use App\Domain\Venue\ValueObject\VenueId;
use PHPUnit\Framework\TestCase;

final class VenueIdTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromStringWithValidUuid(): void
    {
        $id = VenueId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }

    public function testFromStringWithInvalidUuidThrows(): void
    {
        $this->expectException(InvalidVenueId::class);

        VenueId::fromString('not-a-uuid');
    }

    public function testFromStringWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidVenueId::class);

        VenueId::fromString('');
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $id = VenueId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }
}
