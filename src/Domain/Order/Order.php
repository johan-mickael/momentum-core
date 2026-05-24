<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Common\Event\RecordsDomainEvents;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Order\Event\OrderCancelled;
use App\Domain\Order\Event\OrderExpired;
use App\Domain\Order\Event\OrderPaid;
use App\Domain\Order\Event\OrderPlaced;
use App\Domain\Order\Exception\CannotPayCancelledOrder;
use App\Domain\Order\Exception\EmptyOrderItems;
use App\Domain\Order\Exception\OrderAlreadyPaid;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

final class Order
{
    use RecordsDomainEvents;
    const int TTL_MINUTE = 15;

    /** @param OrderItem[] $items */
    private function __construct(
        private readonly OrderId $id,
        private readonly UserId $buyerId,
        private readonly EventId $eventId,
        private readonly array $items,
        private readonly Money $total,
        private OrderStatus $status,
        private readonly DateTimeImmutable $placedAt,
        private readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $paidAt,
        private ?DateTimeImmutable $cancelledAt,
    ) {
    }

    /** @param OrderItem[] $items */
    public static function place(
        OrderId $id,
        UserId $buyerId,
        EventId $eventId,
        array $items,
        DateTimeImmutable $placedAt,
        int $ttlMinutes = self::TTL_MINUTE,
    ): self {
        if (empty($items)) {
            throw EmptyOrderItems::create();
        }

        $order = new self(
            id: $id,
            buyerId: $buyerId,
            eventId: $eventId,
            items: $items,
            total: self::computeTotal($items),
            status: OrderStatus::PENDING_PAYMENT,
            placedAt: $placedAt,
            expiresAt: $placedAt->modify("+{$ttlMinutes} minutes"),
            paidAt: null,
            cancelledAt: null,
        );

        $order->recordEvent(new OrderPlaced(
            orderId: $id->toString(),
            buyerId: (string) $buyerId,
            eventId: (string) $eventId,
            ticketIds: array_map(fn(OrderItem $item) => $item->ticketId()->toString(), $items),
        ));

        return $order;
    }

    /** @param OrderItem[] $items */
    public static function reconstitute(
        OrderId $id,
        UserId $buyerId,
        EventId $eventId,
        array $items,
        Money $total,
        OrderStatus $status,
        DateTimeImmutable $placedAt,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $cancelledAt,
    ): self {
        return new self(
            id: $id,
            buyerId: $buyerId,
            eventId: $eventId,
            items: $items,
            total: $total,
            status: $status,
            placedAt: $placedAt,
            expiresAt: $expiresAt,
            paidAt: $paidAt,
            cancelledAt: $cancelledAt,
        );
    }

    public function markAsPaid(DateTimeImmutable $paidAt): void
    {
        if ($this->status === OrderStatus::PAID) {
            throw OrderAlreadyPaid::forOrder($this->id);
        }

        if ($this->status === OrderStatus::CANCELLED) {
            throw CannotPayCancelledOrder::forOrder($this->id);
        }

        $this->status = OrderStatus::PAID;
        $this->paidAt = $paidAt;

        $this->recordEvent(new OrderPaid(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn(OrderItem $item) => $item->ticketId()->toString(), $this->items),
        ));
    }

    public function expire(DateTimeImmutable $now): void
    {
        if ($this->status !== OrderStatus::PENDING_PAYMENT) {
            return;
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = $now;

        $this->recordEvent(new OrderExpired(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn(OrderItem $item) => $item->ticketId()->toString(), $this->items),
        ));
    }

    public function cancel(DateTimeImmutable $cancelledAt): void
    {
        if ($this->status !== OrderStatus::PENDING_PAYMENT) {
            return;
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = $cancelledAt;

        $this->recordEvent(new OrderCancelled(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn(OrderItem $item) => $item->ticketId()->toString(), $this->items),
        ));
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function buyerId(): UserId
    {
        return $this->buyerId;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    /** @return OrderItem[] */
    public function items(): array
    {
        return $this->items;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function placedAt(): DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /** @param OrderItem[] $items */
    private static function computeTotal(array $items): Money
    {
        $total = new Money(0, $items[0]->unitPrice()->currency());

        foreach ($items as $item) {
            $total = $total->add($item->unitPrice());
        }

        return $total;
    }
}
