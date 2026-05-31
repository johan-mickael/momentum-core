<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\ValueObject;

use Domain\Event\Exception\InvalidTicketCategoryId;
use Domain\Event\ValueObject\TicketCategoryId;
use PHPUnit\Framework\TestCase;

final class TicketCategoryIdTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';
    private const string ANOTHER_UUID = '223e4567-e89b-12d3-a456-426614174001';

    public function testFromStringWithValidUuid(): void
    {
        $id = TicketCategoryId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, $id->toString());
    }

    public function testFromStringWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidTicketCategoryId::class);

        TicketCategoryId::fromString('');
    }

    public function testFromStringWithInvalidUuidThrows(): void
    {
        $this->expectException(InvalidTicketCategoryId::class);

        TicketCategoryId::fromString('not-a-uuid');
    }

    public function testEqualsReturnsTrueForSameId(): void
    {
        $a = TicketCategoryId::fromString(self::VALID_UUID);
        $b = TicketCategoryId::fromString(self::VALID_UUID);

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentId(): void
    {
        $a = TicketCategoryId::fromString(self::VALID_UUID);
        $b = TicketCategoryId::fromString(self::ANOTHER_UUID);

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $id = TicketCategoryId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, $id->toString());
    }
}
