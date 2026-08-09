<?php

declare(strict_types=1);

namespace Domain\Cart;

use Domain\Cart\DomainEvent\CartCheckedOut;
use Domain\Cart\DomainEvent\CartExpired;
use Domain\Cart\DomainEvent\CartOpened;
use Domain\Cart\Exception\CartAlreadyExpired;
use Domain\Cart\Exception\CartItemNotFound;
use Domain\Cart\Exception\CartItemQuantityInvalid;
use Domain\Cart\Exception\CartLineLimitExceeded;
use Domain\Cart\Exception\CartNotActive;
use Domain\Cart\Exception\EmptyCart;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Event\RecordsDomainEvents;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\User\ValueObject\UserId;

final class Cart
{
    use RecordsDomainEvents;

    public const int TTL_MINUTES = 10;
    public const int MAX_QUANTITY_PER_CATEGORY = 20;
    public const int MAX_DISTINCT_CATEGORIES = 20;

    /** @var array<string, CartItem> keyed by TicketCategoryId string */
    private array $items = [];

    private function __construct(
        private readonly CartId $id,
        private readonly UserId $buyerId,
        private readonly EventId $eventId,
        private CartStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $expiresAt,
    ) {
    }

    public static function open(CartId $id, UserId $buyerId, EventId $eventId, \DateTimeImmutable $now): self
    {
        $cart = new self(
            id: $id,
            buyerId: $buyerId,
            eventId: $eventId,
            status: CartStatus::ACTIVE,
            createdAt: $now,
            expiresAt: $now->modify('+'.self::TTL_MINUTES.' minutes'),
        );

        $cart->recordEvent(new CartOpened(
            cartId: $id->toString(),
            buyerId: (string) $buyerId,
            eventId: (string) $eventId,
        ));

        return $cart;
    }

    /** @param CartItem[] $items */
    public static function reconstitute(
        CartId $id,
        UserId $buyerId,
        EventId $eventId,
        CartStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
        array $items,
    ): self {
        $cart = new self($id, $buyerId, $eventId, $status, $createdAt, $expiresAt);

        foreach ($items as $item) {
            $cart->items[$item->categoryId()->toString()] = $item;
        }

        return $cart;
    }

    public function addItem(TicketCategoryId $categoryId, int $quantity, \DateTimeImmutable $now): void
    {
        $this->assertActive($now);
        $this->assertPositiveQuantity($quantity);

        $newQuantity = $this->quantityOf($categoryId) + $quantity;
        $this->assertQuantityWithinLimit($newQuantity);

        if (!isset($this->items[$categoryId->toString()]) && count($this->items) >= self::MAX_DISTINCT_CATEGORIES) {
            throw CartLineLimitExceeded::create(self::MAX_DISTINCT_CATEGORIES);
        }

        $this->items[$categoryId->toString()] = new CartItem($categoryId, $newQuantity, $now);
        $this->touch($now);
    }

    public function setItemQuantity(TicketCategoryId $categoryId, int $quantity, \DateTimeImmutable $now): void
    {
        $this->assertActive($now);

        if (0 === $quantity) {
            $this->removeItem($categoryId, $now);

            return;
        }

        $this->assertPositiveQuantity($quantity);
        $this->assertQuantityWithinLimit($quantity);

        $addedAt = ($this->items[$categoryId->toString()] ?? null)?->addedAt() ?? $now;
        $this->items[$categoryId->toString()] = new CartItem($categoryId, $quantity, $addedAt);
        $this->touch($now);
    }

    public function removeItem(TicketCategoryId $categoryId, \DateTimeImmutable $now): void
    {
        $this->assertActive($now);

        if (!isset($this->items[$categoryId->toString()])) {
            throw CartItemNotFound::forCategory($categoryId);
        }

        unset($this->items[$categoryId->toString()]);
        $this->touch($now);
    }

    public function clear(\DateTimeImmutable $now): void
    {
        $this->assertActive($now);
        $this->items = [];
        $this->touch($now);
    }

    public function checkout(\DateTimeImmutable $now): void
    {
        $this->assertActive($now);

        if (empty($this->items)) {
            throw EmptyCart::create();
        }

        $this->status = CartStatus::CHECKED_OUT;

        $this->recordEvent(new CartCheckedOut(
            cartId: $this->id->toString(),
            eventId: (string) $this->eventId,
        ));
    }

    public function expire(\DateTimeImmutable $now): void
    {
        if (CartStatus::ACTIVE !== $this->status) {
            return;
        }

        $heldQuantities = array_map(
            static fn (CartItem $item) => [
                'categoryId' => $item->categoryId()->toString(),
                'quantity' => $item->quantity(),
            ],
            $this->items(),
        );

        $this->status = CartStatus::EXPIRED;

        $this->recordEvent(new CartExpired(
            cartId: $this->id->toString(),
            heldQuantities: $heldQuantities,
        ));
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return CartStatus::ACTIVE === $this->status && $now >= $this->expiresAt;
    }

    public function quantityOf(TicketCategoryId $categoryId): int
    {
        return ($this->items[$categoryId->toString()] ?? null)?->quantity() ?? 0;
    }

    /**
     * A lapsed-but-not-yet-swept cart must never be treated as usable: the
     * periodic sweep may not have run yet, so status alone can lag reality.
     */
    private function assertActive(\DateTimeImmutable $now): void
    {
        if (CartStatus::ACTIVE !== $this->status) {
            throw CartNotActive::forCart($this->id, $this->status);
        }

        if ($this->isExpired($now)) {
            throw CartAlreadyExpired::forCart($this->id);
        }
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw CartItemQuantityInvalid::notPositive($quantity);
        }
    }

    private function assertQuantityWithinLimit(int $quantity): void
    {
        if ($quantity > self::MAX_QUANTITY_PER_CATEGORY) {
            throw CartItemQuantityInvalid::exceedsLimitPerCategory($quantity, self::MAX_QUANTITY_PER_CATEGORY);
        }
    }

    private function touch(\DateTimeImmutable $now): void
    {
        $this->expiresAt = $now->modify('+'.self::TTL_MINUTES.' minutes');
    }

    /** @return CartItem[] */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function id(): CartId
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

    public function status(): CartStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
