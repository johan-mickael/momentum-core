<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Order\Exception\CannotPayCancelledOrder;
use App\Domain\Order\Exception\OrderAlreadyPaid;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\Exception\EmptyOrderItems;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private const string ORDER_ID    = '019e0000-0000-7000-8000-000000000001';
    private const string BUYER_ID    = '019e0000-0000-7000-8000-000000000002';
    private const string EVENT_ID    = '019e0000-0000-7000-8000-000000000003';
    private const string CATEGORY_ID = '019e0000-0000-7000-8000-000000000004';
    private const string TICKET_ID   = '019e0000-0000-7000-8000-000000000005';

    private OrderId  $orderId;
    private UserId   $buyerId;
    private EventId  $eventId;
    private Money    $price;
    private OrderItem $item;

    protected function setUp(): void
    {
        $this->orderId = OrderId::fromString(self::ORDER_ID);
        $this->buyerId = UserId::fromString(self::BUYER_ID);
        $this->eventId = EventId::fromString(self::EVENT_ID);
        $this->price   = new Money(5000, Currency::fromString('EUR'));
        $this->item    = new OrderItem(
            TicketCategoryId::fromString(self::CATEGORY_ID),
            TicketId::fromString(self::TICKET_ID),
            $this->price,
        );
    }

    // ─── place() ─────────────────────────────────────────────────────────────

    public function testPlaceCreatesOrderWithPendingPaymentStatus(): void
    {
        $order = $this->makePendingOrder();

        self::assertSame(OrderStatus::PENDING_PAYMENT, $order->status());
    }

    public function testPlaceComputesTotalFromItems(): void
    {
        $order = $this->makePendingOrder();

        self::assertSame(5000, $order->total()->amount());
        self::assertSame('EUR', $order->total()->currency()->toString());
    }

    public function testPlaceWithMultipleItemsSumsPricesCorrectly(): void
    {
        $item2 = new OrderItem(
            TicketCategoryId::fromString(self::CATEGORY_ID),
            TicketId::fromString('019e0000-0000-7000-8000-000000000006'),
            new Money(3000, Currency::fromString('EUR')),
        );

        $order = Order::place(
            id:       $this->orderId,
            buyerId:  $this->buyerId,
            eventId:  $this->eventId,
            items:    [$this->item, $item2],
            placedAt: new DateTimeImmutable('2040-01-01 10:00:00'),
        );

        self::assertSame(8000, $order->total()->amount());
        self::assertCount(2, $order->items());
    }

    public function testPlaceSetsExpiresAtFifteenMinutesAfterPlacedAt(): void
    {
        $placedAt = new DateTimeImmutable('2040-01-01 10:00:00');
        $order    = Order::place(
            id:       $this->orderId,
            buyerId:  $this->buyerId,
            eventId:  $this->eventId,
            items:    [$this->item],
            placedAt: $placedAt,
        );

        self::assertEquals(
            new DateTimeImmutable('2040-01-01 10:15:00'),
            $order->expiresAt(),
        );
    }

    public function testPlaceWithCustomTtlMinutesRespectsTtl(): void
    {
        $placedAt = new DateTimeImmutable('2040-01-01 10:00:00');
        $order    = Order::place(
            id:         $this->orderId,
            buyerId:    $this->buyerId,
            eventId:    $this->eventId,
            items:      [$this->item],
            placedAt:   $placedAt,
            ttlMinutes: 30,
        );

        self::assertEquals(
            new DateTimeImmutable('2040-01-01 10:30:00'),
            $order->expiresAt(),
        );
    }

    public function testPlaceWithEmptyItemsThrows(): void
    {
        $this->expectException(EmptyOrderItems::class);

        Order::place(
            id:       $this->orderId,
            buyerId:  $this->buyerId,
            eventId:  $this->eventId,
            items:    [],
            placedAt: new DateTimeImmutable(),
        );
    }

    public function testPlaceSetsNullPaidAtAndCancelledAt(): void
    {
        $order = $this->makePendingOrder();

        self::assertNull($order->paidAt());
        self::assertNull($order->cancelledAt());
    }

    // ─── markAsPaid() ────────────────────────────────────────────────────────

    public function testMarkAsPaidTransitionsToPaidStatus(): void
    {
        $order  = $this->makePendingOrder();
        $paidAt = new DateTimeImmutable('2040-01-01 10:05:00');

        $order->markAsPaid($paidAt);

        self::assertSame(OrderStatus::PAID, $order->status());
        self::assertEquals($paidAt, $order->paidAt());
    }

    public function testMarkAsPaidOnAlreadyPaidOrderThrows(): void
    {
        $order = $this->makePendingOrder();
        $order->markAsPaid(new DateTimeImmutable());

        $this->expectException(OrderAlreadyPaid::class);

        $order->markAsPaid(new DateTimeImmutable());
    }

    public function testMarkAsPaidOnCancelledOrderThrows(): void
    {
        $order = $this->makePendingOrder();
        $order->expire(new DateTimeImmutable());

        $this->expectException(CannotPayCancelledOrder::class);

        $order->markAsPaid(new DateTimeImmutable());
    }

    // ─── expire() ────────────────────────────────────────────────────────────

    public function testExpireTransitionsToCancelledStatus(): void
    {
        $order = $this->makePendingOrder();
        $now   = new DateTimeImmutable('2040-01-01 10:20:00');

        $order->expire($now);

        self::assertSame(OrderStatus::CANCELLED, $order->status());
        self::assertEquals($now, $order->cancelledAt());
    }

    public function testExpireOnPaidOrderIsIdempotent(): void
    {
        $order = $this->makePendingOrder();
        $order->markAsPaid(new DateTimeImmutable());

        $order->expire(new DateTimeImmutable());

        self::assertSame(OrderStatus::PAID, $order->status()); // unchanged
    }

    public function testExpireOnAlreadyCancelledOrderIsIdempotent(): void
    {
        $order   = $this->makePendingOrder();
        $firstAt = new DateTimeImmutable('2040-01-01 10:16:00');
        $order->expire($firstAt);

        $order->expire(new DateTimeImmutable('2040-01-01 10:20:00'));

        self::assertEquals($firstAt, $order->cancelledAt()); // unchanged
    }

    // ─── accessors ───────────────────────────────────────────────────────────

    public function testAccessorsReturnCorrectValues(): void
    {
        $placedAt = new DateTimeImmutable('2040-01-01 10:00:00');
        $order    = Order::place(
            id:       $this->orderId,
            buyerId:  $this->buyerId,
            eventId:  $this->eventId,
            items:    [$this->item],
            placedAt: $placedAt,
        );

        self::assertTrue($this->orderId->equals($order->id()));
        self::assertSame((string) $this->buyerId, (string) $order->buyerId());
        self::assertSame((string) $this->eventId, (string) $order->eventId());
        self::assertCount(1, $order->items());
        self::assertEquals($placedAt, $order->placedAt());
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function makePendingOrder(): Order
    {
        return Order::place(
            id:       $this->orderId,
            buyerId:  $this->buyerId,
            eventId:  $this->eventId,
            items:    [$this->item],
            placedAt: new DateTimeImmutable('2040-01-01 10:00:00'),
        );
    }
}
