<?php

declare(strict_types=1);

namespace Domain\Order;

use Domain\Common\Event\RecordsDomainEvents;
use Domain\Common\ValueObject\Money;
use Domain\Event\ValueObject\EventId;
use Domain\Order\Event\OrderCancelled;
use Domain\Order\Event\OrderExpired;
use Domain\Order\Event\OrderPaid;
use Domain\Order\Event\OrderPlaced;
use Domain\Order\Event\OrderRefunded;
use Domain\Order\Exception\CannotPayCancelledOrder;
use Domain\Order\Exception\CannotRefundOrder;
use Domain\Order\Exception\EmptyOrderItems;
use Domain\Order\Exception\OrderAlreadyPaid;
use Domain\Order\ValueObject\OrderId;
use Domain\Order\ValueObject\PaymentReference;
use Domain\User\ValueObject\UserId;

final class Order
{
    use RecordsDomainEvents;
    public const int TTL_MINUTE = 15;

    /** @param OrderItem[] $items */
    private function __construct(
        private readonly OrderId $id,
        private readonly UserId $buyerId,
        private readonly EventId $eventId,
        private readonly array $items,
        private readonly Money $total,
        private OrderStatus $status,
        private readonly \DateTimeImmutable $placedAt,
        private readonly \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $paidAt,
        private ?\DateTimeImmutable $cancelledAt,
        private ?PaymentReference $paymentReference,
        private ?string $checkoutSessionId = null,
    ) {
    }

    /** @param OrderItem[] $items */
    public static function place(
        OrderId $id,
        UserId $buyerId,
        EventId $eventId,
        array $items,
        \DateTimeImmutable $placedAt,
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
            paymentReference: null,
            checkoutSessionId: null,
        );

        $order->recordEvent(new OrderPlaced(
            orderId: $id->toString(),
            buyerId: (string) $buyerId,
            eventId: (string) $eventId,
            ticketIds: array_map(fn (OrderItem $item) => $item->ticketId()->toString(), $items),
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
        \DateTimeImmutable $placedAt,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $paidAt,
        ?\DateTimeImmutable $cancelledAt,
        ?PaymentReference $paymentReference,
        ?string $checkoutSessionId = null,
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
            paymentReference: $paymentReference,
            checkoutSessionId: $checkoutSessionId,
        );
    }

    public function markAsPaid(\DateTimeImmutable $paidAt, ?PaymentReference $paymentReference = null): void
    {
        if (OrderStatus::PAID === $this->status) {
            throw OrderAlreadyPaid::forOrder($this->id);
        }

        if (OrderStatus::CANCELLED === $this->status) {
            throw CannotPayCancelledOrder::forOrder($this->id);
        }

        $this->status = OrderStatus::PAID;
        $this->paidAt = $paidAt;
        $this->paymentReference = $paymentReference;

        $this->recordEvent(new OrderPaid(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn (OrderItem $item) => $item->ticketId()->toString(), $this->items),
        ));
    }

    public function refund(\DateTimeImmutable $refundedAt): void
    {
        if (OrderStatus::PAID !== $this->status) {
            throw CannotRefundOrder::notPaid($this->id, $this->status);
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = $refundedAt;

        $this->recordEvent(new OrderRefunded(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn (OrderItem $item) => $item->ticketId()->toString(), $this->items),
            paymentReference: $this->paymentReference?->toString() ?? '',
        ));
    }

    public function expire(\DateTimeImmutable $now): void
    {
        if (OrderStatus::PENDING_PAYMENT !== $this->status) {
            return;
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = $now;

        $this->recordEvent(new OrderExpired(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn (OrderItem $item) => $item->ticketId()->toString(), $this->items),
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if (OrderStatus::PENDING_PAYMENT !== $this->status) {
            return;
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = $cancelledAt;

        $this->recordEvent(new OrderCancelled(
            orderId: $this->id->toString(),
            ticketIds: array_map(fn (OrderItem $item) => $item->ticketId()->toString(), $this->items),
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

    public function placedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function cancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function paymentReference(): ?PaymentReference
    {
        return $this->paymentReference;
    }

    public function storeCheckoutSession(string $sessionId): void
    {
        $this->checkoutSessionId = $sessionId;
    }

    public function checkoutSessionId(): ?string
    {
        return $this->checkoutSessionId;
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
