<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cart;

use Domain\Cart\Cart;
use Domain\Cart\CartItem;
use Domain\Cart\CartStatus;
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
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    private const string CART_ID = '019e0000-0000-7000-8000-000000000001';
    private const string BUYER_ID = '019e0000-0000-7000-8000-000000000002';
    private const string EVENT_ID = '019e0000-0000-7000-8000-000000000003';
    private const string CATEGORY_ID = '019e0000-0000-7000-8000-000000000004';
    private const string ANOTHER_CATEGORY_ID = '019e0000-0000-7000-8000-000000000005';

    private CartId $cartId;
    private UserId $buyerId;
    private EventId $eventId;
    private TicketCategoryId $categoryId;

    protected function setUp(): void
    {
        $this->cartId = CartId::fromString(self::CART_ID);
        $this->buyerId = UserId::fromString(self::BUYER_ID);
        $this->eventId = EventId::fromString(self::EVENT_ID);
        $this->categoryId = TicketCategoryId::fromString(self::CATEGORY_ID);
    }

    // ─── open() ─────────────────────────────────────────────────────────────

    public function testOpenCreatesActiveCart(): void
    {
        $cart = $this->openCart();

        self::assertSame(CartStatus::ACTIVE, $cart->status());
        self::assertSame([], $cart->items());
    }

    public function testOpenSetsExpiresAtTenMinutesAfterNow(): void
    {
        $now = new \DateTimeImmutable('2040-01-01 10:00:00');
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, $now);

        self::assertEquals(new \DateTimeImmutable('2040-01-01 10:10:00'), $cart->expiresAt());
    }

    public function testOpenRecordsCartOpenedEvent(): void
    {
        $cart = $this->openCart();

        $events = $cart->pullRecordedEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(CartOpened::class, $events[0]);
        self::assertSame(self::CART_ID, $events[0]->cartId);
        self::assertSame(self::BUYER_ID, $events[0]->buyerId);
        self::assertSame(self::EVENT_ID, $events[0]->eventId);
    }

    // ─── addItem() ──────────────────────────────────────────────────────────

    public function testAddItemAddsNewLine(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable('2040-01-01 10:00:00');

        $cart->addItem($this->categoryId, 2, $now);

        self::assertSame(2, $cart->quantityOf($this->categoryId));
        self::assertCount(1, $cart->items());
    }

    public function testAddItemTwiceMergesQuantityOnSameCategory(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable('2040-01-01 10:00:00');

        $cart->addItem($this->categoryId, 2, $now);
        $cart->addItem($this->categoryId, 3, $now);

        self::assertSame(5, $cart->quantityOf($this->categoryId));
        self::assertCount(1, $cart->items());
    }

    public function testAddItemExtendsExpiresAt(): void
    {
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));

        $later = new \DateTimeImmutable('2040-01-01 10:05:00');
        $cart->addItem($this->categoryId, 1, $later);

        self::assertEquals(new \DateTimeImmutable('2040-01-01 10:15:00'), $cart->expiresAt());
    }

    public function testAddItemWithZeroQuantityThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(CartItemQuantityInvalid::class);

        $cart->addItem($this->categoryId, 0, new \DateTimeImmutable());
    }

    public function testAddItemWithNegativeQuantityThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(CartItemQuantityInvalid::class);

        $cart->addItem($this->categoryId, -1, new \DateTimeImmutable());
    }

    public function testAddItemExceedingPerCategoryLimitThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(CartItemQuantityInvalid::class);

        $cart->addItem($this->categoryId, Cart::MAX_QUANTITY_PER_CATEGORY + 1, new \DateTimeImmutable());
    }

    public function testAddItemAccumulatingAboveLimitAcrossCallsThrows(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, Cart::MAX_QUANTITY_PER_CATEGORY, $now);

        $this->expectException(CartItemQuantityInvalid::class);

        $cart->addItem($this->categoryId, 1, $now);
    }

    public function testAddItemBeyondDistinctCategoryLimitThrows(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();

        for ($i = 0; $i < Cart::MAX_DISTINCT_CATEGORIES; ++$i) {
            $cart->addItem(TicketCategoryId::fromString(sprintf('019e0000-0000-7000-9000-%012d', $i)), 1, $now);
        }

        $this->expectException(CartLineLimitExceeded::class);

        $cart->addItem(TicketCategoryId::fromString('019e0000-0000-7000-9000-999999999999'), 1, $now);
    }

    public function testAddItemOnCheckedOutCartThrows(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->checkout($now);

        $this->expectException(CartNotActive::class);

        $cart->addItem($this->categoryId, 1, $now);
    }

    public function testAddItemOnLapsedCartThrowsAlreadyExpiredEvenIfStatusStillActive(): void
    {
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));

        $this->expectException(CartAlreadyExpired::class);

        // Status is still ACTIVE in memory (the sweep hasn't run), but time
        // has moved past expiresAt — must be treated as expired regardless.
        $cart->addItem($this->categoryId, 1, new \DateTimeImmutable('2040-01-01 10:11:00'));
    }

    // ─── setItemQuantity() ──────────────────────────────────────────────────

    public function testSetItemQuantityOverwritesQuantity(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 2, $now);

        $cart->setItemQuantity($this->categoryId, 7, $now);

        self::assertSame(7, $cart->quantityOf($this->categoryId));
    }

    public function testSetItemQuantityToZeroRemovesTheLine(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 2, $now);

        $cart->setItemQuantity($this->categoryId, 0, $now);

        self::assertSame(0, $cart->quantityOf($this->categoryId));
        self::assertCount(0, $cart->items());
    }

    public function testSetItemQuantityToZeroOnMissingItemThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(CartItemNotFound::class);

        $cart->setItemQuantity($this->categoryId, 0, new \DateTimeImmutable());
    }

    public function testSetItemQuantityPreservesOriginalAddedAt(): void
    {
        $cart = $this->openCart();
        $addedAt = new \DateTimeImmutable('2040-01-01 10:00:00');
        $cart->addItem($this->categoryId, 1, $addedAt);

        $cart->setItemQuantity($this->categoryId, 3, new \DateTimeImmutable('2040-01-01 10:05:00'));

        $item = $cart->items()[0];
        self::assertEquals($addedAt, $item->addedAt());
    }

    public function testSetItemQuantityExceedingLimitThrows(): void
    {
        $cart = $this->openCart();
        $cart->addItem($this->categoryId, 1, new \DateTimeImmutable());

        $this->expectException(CartItemQuantityInvalid::class);

        $cart->setItemQuantity($this->categoryId, Cart::MAX_QUANTITY_PER_CATEGORY + 1, new \DateTimeImmutable());
    }

    // ─── removeItem() ───────────────────────────────────────────────────────

    public function testRemoveItemRemovesTheLine(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);

        $cart->removeItem($this->categoryId, $now);

        self::assertCount(0, $cart->items());
    }

    public function testRemoveItemNotInCartThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(CartItemNotFound::class);

        $cart->removeItem($this->categoryId, new \DateTimeImmutable());
    }

    public function testRemoveItemOnlyAffectsTargetedCategory(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->addItem(TicketCategoryId::fromString(self::ANOTHER_CATEGORY_ID), 2, $now);

        $cart->removeItem($this->categoryId, $now);

        self::assertCount(1, $cart->items());
        self::assertSame(2, $cart->quantityOf(TicketCategoryId::fromString(self::ANOTHER_CATEGORY_ID)));
    }

    // ─── clear() ────────────────────────────────────────────────────────────

    public function testClearEmptiesAllItems(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->addItem(TicketCategoryId::fromString(self::ANOTHER_CATEGORY_ID), 2, $now);

        $cart->clear($now);

        self::assertCount(0, $cart->items());
    }

    public function testClearOnEmptyCartIsAllowed(): void
    {
        $cart = $this->openCart();

        $cart->clear(new \DateTimeImmutable());

        self::assertCount(0, $cart->items());
    }

    // ─── checkout() ─────────────────────────────────────────────────────────

    public function testCheckoutTransitionsToCheckedOut(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);

        $cart->checkout($now);

        self::assertSame(CartStatus::CHECKED_OUT, $cart->status());
    }

    public function testCheckoutRecordsCartCheckedOutEvent(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->pullRecordedEvents(); // discard CartOpened

        $cart->checkout($now);

        $events = $cart->pullRecordedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CartCheckedOut::class, $events[0]);
    }

    public function testCheckoutOnEmptyCartThrows(): void
    {
        $cart = $this->openCart();

        $this->expectException(EmptyCart::class);

        $cart->checkout(new \DateTimeImmutable());
    }

    public function testCheckoutTwiceThrowsCartNotActive(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->checkout($now);

        $this->expectException(CartNotActive::class);

        $cart->checkout($now);
    }

    public function testCheckoutOnLapsedCartThrowsAlreadyExpired(): void
    {
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));
        $cart->addItem($this->categoryId, 1, new \DateTimeImmutable('2040-01-01 10:00:00'));

        $this->expectException(CartAlreadyExpired::class);

        $cart->checkout(new \DateTimeImmutable('2040-01-01 10:11:00'));
    }

    // ─── expire() ───────────────────────────────────────────────────────────

    public function testExpireTransitionsActiveCartToExpired(): void
    {
        $cart = $this->openCart();
        $cart->addItem($this->categoryId, 3, new \DateTimeImmutable());

        $cart->expire(new \DateTimeImmutable());

        self::assertSame(CartStatus::EXPIRED, $cart->status());
    }

    public function testExpireRecordsCartExpiredEventWithHeldQuantities(): void
    {
        $cart = $this->openCart();
        $cart->addItem($this->categoryId, 3, new \DateTimeImmutable());
        $cart->pullRecordedEvents(); // discard CartOpened

        $cart->expire(new \DateTimeImmutable());

        $events = $cart->pullRecordedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CartExpired::class, $events[0]);
        self::assertSame([['categoryId' => self::CATEGORY_ID, 'quantity' => 3]], $events[0]->heldQuantities);
    }

    public function testExpireOnAlreadyCheckedOutCartIsNoOp(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->addItem($this->categoryId, 1, $now);
        $cart->checkout($now);
        $cart->pullRecordedEvents();

        $cart->expire($now);

        self::assertSame(CartStatus::CHECKED_OUT, $cart->status());
        self::assertCount(0, $cart->pullRecordedEvents());
    }

    public function testExpireOnAlreadyExpiredCartIsIdempotent(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable();
        $cart->expire($now);
        $cart->pullRecordedEvents();

        $cart->expire($now);

        self::assertSame(CartStatus::EXPIRED, $cart->status());
        self::assertCount(0, $cart->pullRecordedEvents());
    }

    // ─── isExpired() ────────────────────────────────────────────────────────

    public function testIsExpiredFalseBeforeTtlLapses(): void
    {
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));

        self::assertFalse($cart->isExpired(new \DateTimeImmutable('2040-01-01 10:09:59')));
    }

    public function testIsExpiredTrueExactlyAtExpiresAt(): void
    {
        $cart = Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));

        self::assertTrue($cart->isExpired(new \DateTimeImmutable('2040-01-01 10:10:00')));
    }

    public function testIsExpiredFalseForCheckedOutCartRegardlessOfTime(): void
    {
        $cart = $this->openCart();
        $now = new \DateTimeImmutable('2040-01-01 10:00:00');
        $cart->addItem($this->categoryId, 1, $now);
        $cart->checkout($now);

        self::assertFalse($cart->isExpired(new \DateTimeImmutable('2099-01-01 10:00:00')));
    }

    // ─── quantityOf() ───────────────────────────────────────────────────────

    public function testQuantityOfReturnsZeroForUnknownCategory(): void
    {
        $cart = $this->openCart();

        self::assertSame(0, $cart->quantityOf($this->categoryId));
    }

    // ─── reconstitute() ─────────────────────────────────────────────────────

    public function testReconstituteRebuildsItemsAndState(): void
    {
        $item = new CartItem($this->categoryId, 4, new \DateTimeImmutable('2040-01-01 10:00:00'));

        $cart = Cart::reconstitute(
            id: $this->cartId,
            buyerId: $this->buyerId,
            eventId: $this->eventId,
            status: CartStatus::ACTIVE,
            createdAt: new \DateTimeImmutable('2040-01-01 10:00:00'),
            expiresAt: new \DateTimeImmutable('2040-01-01 10:10:00'),
            items: [$item],
        );

        self::assertSame(CartStatus::ACTIVE, $cart->status());
        self::assertSame(4, $cart->quantityOf($this->categoryId));
        self::assertCount(0, $cart->pullRecordedEvents()); // no events replayed on reconstitute
    }

    private function openCart(): Cart
    {
        return Cart::open($this->cartId, $this->buyerId, $this->eventId, new \DateTimeImmutable('2040-01-01 10:00:00'));
    }
}
