<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ticket\ValueObject;

use App\Domain\Ticket\Exception\InvalidTicketId;
use App\Domain\Ticket\ValueObject\TicketId;
use PHPUnit\Framework\TestCase;

final class TicketIdTest extends TestCase
{
    public function testFromStringWithValidValue(): void
    {
        $id = TicketId::fromString('ticket-abc-123');

        self::assertSame('ticket-abc-123', $id->toString());
    }

    public function testFromStringAcceptsAnyNonEmptyString(): void
    {
        // TicketId validates emptiness only, not UUID format.
        $id = TicketId::fromString('123e4567-e89b-12d3-a456-426614174000');

        self::assertSame('123e4567-e89b-12d3-a456-426614174000', $id->toString());
    }

    public function testFromStringWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidTicketId::class);

        TicketId::fromString('');
    }

    public function testFromStringWithWhitespaceOnlyThrows(): void
    {
        $this->expectException(InvalidTicketId::class);

        TicketId::fromString('   ');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $a = TicketId::fromString('ticket-1');
        $b = TicketId::fromString('ticket-1');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $a = TicketId::fromString('ticket-1');
        $b = TicketId::fromString('ticket-2');

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $id = TicketId::fromString('my-ticket-id');

        self::assertSame('my-ticket-id', $id->toString());
    }
}
