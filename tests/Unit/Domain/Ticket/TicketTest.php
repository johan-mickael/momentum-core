<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ticket;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Exception\InvalidTicketStatusTransition;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketStatus;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    private const string TICKET_ID    = 'ticket-0001';
    private const string EVENT_UUID   = '123e4567-e89b-12d3-a456-426614174000';
    private const string CATEGORY_UUID = '223e4567-e89b-12d3-a456-426614174001';
    private const string HOLDER_UUID  = '323e4567-e89b-12d3-a456-426614174002';

    private TicketId $id;
    private EventId $eventId;
    private TicketCategoryId $categoryId;
    private UserId $holderId;
    private Money $price;
    private DateTimeImmutable $reservedAt;

    protected function setUp(): void
    {
        $this->id         = TicketId::fromString(self::TICKET_ID);
        $this->eventId    = EventId::fromString(self::EVENT_UUID);
        $this->categoryId = TicketCategoryId::fromString(self::CATEGORY_UUID);
        $this->holderId   = UserId::fromString(self::HOLDER_UUID);
        $this->price      = new Money(5000, Currency::fromString('EUR'));
        $this->reservedAt = new DateTimeImmutable('2026-06-01 10:00:00');
    }

    // -------------------------------------------------------------------------
    // reserve()
    // -------------------------------------------------------------------------

    public function testReserveCreatesTicketWithCorrectProperties(): void
    {
        $ticket = $this->makeTicket();

        self::assertSame($this->id, $ticket->id());
        self::assertSame($this->eventId, $ticket->eventId());
        self::assertSame($this->categoryId, $ticket->categoryId());
        self::assertSame($this->holderId, $ticket->holderId());
        self::assertTrue($ticket->price()->equals($this->price));
        self::assertSame($this->reservedAt, $ticket->reservedAt());
    }

    public function testReservedTicketHasReservedStatus(): void
    {
        $ticket = $this->makeTicket();

        self::assertSame(TicketStatus::RESERVED, $ticket->status());
    }

    // -------------------------------------------------------------------------
    // confirm()
    // -------------------------------------------------------------------------

    public function testConfirmChangesStatusFromReservedToConfirmed(): void
    {
        $ticket = $this->makeTicket();

        $ticket->confirm();

        self::assertSame(TicketStatus::CONFIRMED, $ticket->status());
    }

    public function testConfirmWhenAlreadyConfirmedThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->confirm();
    }

    public function testConfirmWhenCancelledThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->cancel();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->confirm();
    }

    public function testConfirmWhenUsedThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();
        $ticket->markAsUsed();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->confirm();
    }

    // -------------------------------------------------------------------------
    // cancel()
    // -------------------------------------------------------------------------

    public function testCancelFromReservedChangesStatusToCancelled(): void
    {
        $ticket = $this->makeTicket();

        $ticket->cancel();

        self::assertSame(TicketStatus::CANCELLED, $ticket->status());
    }

    public function testCancelFromConfirmedChangesStatusToCancelled(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();

        $ticket->cancel();

        self::assertSame(TicketStatus::CANCELLED, $ticket->status());
    }

    public function testCancelWhenAlreadyCancelledThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->cancel();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->cancel();
    }

    public function testCancelWhenUsedThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();
        $ticket->markAsUsed();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->cancel();
    }

    // -------------------------------------------------------------------------
    // markAsUsed()
    // -------------------------------------------------------------------------

    public function testMarkAsUsedFromConfirmedChangesStatusToUsed(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();

        $ticket->markAsUsed();

        self::assertSame(TicketStatus::USED, $ticket->status());
    }

    public function testMarkAsUsedWhenReservedThrows(): void
    {
        $ticket = $this->makeTicket();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->markAsUsed();
    }

    public function testMarkAsUsedWhenCancelledThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->cancel();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->markAsUsed();
    }

    public function testMarkAsUsedWhenAlreadyUsedThrows(): void
    {
        $ticket = $this->makeTicket();
        $ticket->confirm();
        $ticket->markAsUsed();

        $this->expectException(InvalidTicketStatusTransition::class);

        $ticket->markAsUsed();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTicket(): Ticket
    {
        return Ticket::reserve(
            id:         $this->id,
            eventId:    $this->eventId,
            categoryId: $this->categoryId,
            holderId:   $this->holderId,
            price:      $this->price,
            reservedAt: $this->reservedAt,
        );
    }
}
