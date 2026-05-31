<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use Domain\Event\Exception\InvalidEventId;
use Domain\Event\ValueObject\EventId;
use PHPUnit\Framework\TestCase;

final class EventIdTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromStringWithValidUuid(): void
    {
        $id = EventId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }

    public function testFromStringWithInvalidUuidThrows(): void
    {
        $this->expectException(InvalidEventId::class);

        EventId::fromString('not-a-uuid');
    }

    public function testFromStringWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidEventId::class);

        EventId::fromString('');
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $id = EventId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }
}
