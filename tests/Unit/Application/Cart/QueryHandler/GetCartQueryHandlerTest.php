<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cart\QueryHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Query\GetCartQuery;
use Application\Cart\QueryHandler\GetCartQueryHandler;
use Application\Cart\Result\CartResult;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\ViewCartForbidden;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\Currency;
use Domain\Common\ValueObject\Money;
use Domain\Event\Event;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\DateRange;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\EventSlug;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\User\Role;
use Domain\User\ValueObject\UserId;
use Domain\Venue\ValueObject\VenueId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class GetCartQueryHandlerTest extends TestCase
{
    private const string BUYER_ID = '019e0000-0000-7000-8000-000000000001';
    private const string EVENT_ID = '019e0000-0000-7000-8000-000000000002';
    private const string CART_ID = '019e0000-0000-7000-8000-000000000003';
    private const string CATEGORY_ID = '019e0000-0000-7000-8000-000000000004';
    private const string OPENED_AT = '2040-01-01 10:00:00';

    private CartRepositoryInterface&MockObject $carts;
    private EventRepositoryInterface&MockObject $events;
    private AuthorizationServiceInterface&MockObject $auth;
    private MockClock $clock;
    private GetCartQueryHandler $handler;

    protected function setUp(): void
    {
        $this->carts = $this->createMock(CartRepositoryInterface::class);
        $this->events = $this->createMock(EventRepositoryInterface::class);
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->auth->method('authorize')->willReturn(true);
        $this->clock = new MockClock(self::OPENED_AT);

        $this->handler = new GetCartQueryHandler($this->carts, $this->events, $this->auth, new CartResultAssembler(), $this->clock);
    }

    public function testNoActiveCartReturnsEmptyResult(): void
    {
        $this->carts->method('findActiveByBuyerAndEvent')->willReturn(null);
        $this->events->expects(self::never())->method('getById');

        $result = ($this->handler)($this->makeQuery(), $this->makeActor());

        self::assertEquals(CartResult::empty(), $result);
    }

    /**
     * Removing a cart's last line leaves it ACTIVE but item-less — only
     * checkout()/expire() ever change status. That state must present the
     * same as "no cart", otherwise CartResultAssembler serializes it with
     * currency: '' (nothing ever sets $currency without an item to read the
     * price from), which crashes the frontend's Intl.NumberFormat.
     */
    public function testActiveButItemLessCartReturnsEmptyResult(): void
    {
        $this->carts->method('findActiveByBuyerAndEvent')->willReturn($this->openCart());
        $this->events->expects(self::never())->method('getById');

        $result = ($this->handler)($this->makeQuery(), $this->makeActor());

        self::assertEquals(CartResult::empty(), $result);
    }

    public function testActiveCartWithItemsIsAssembledFromLiveEventPricing(): void
    {
        $cart = $this->openCart();
        $cart->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 2, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findActiveByBuyerAndEvent')->willReturn($cart);
        $this->events->method('getById')->willReturn($this->makeEvent());

        $result = ($this->handler)($this->makeQuery(), $this->makeActor());

        self::assertSame('active', $result->status);
        self::assertSame('EUR', $result->currency);
        self::assertSame(5000, $result->totalAmount);
        self::assertCount(1, $result->items);
        self::assertSame(2, $result->items[0]->quantity);
    }

    public function testViewerWhoIsNotTheOwnerIsDenied(): void
    {
        $cart = $this->openCart();
        $cart->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 1, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findActiveByBuyerAndEvent')->willReturn($cart);
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->auth->method('authorize')->willReturn(false);
        $this->handler = new GetCartQueryHandler($this->carts, $this->events, $this->auth, new CartResultAssembler(), $this->clock);

        $this->expectException(ViewCartForbidden::class);

        ($this->handler)($this->makeQuery(), $this->makeActor());
    }

    /**
     * The periodic sweep (ExpireCartsTask) only runs every 30s — a lapsed
     * cart sits ACTIVE in the DB until then. Without checking isExpired()
     * here, the buyer's own read would keep showing it as fully usable for
     * up to that long after its TTL actually ran out, right up until a
     * mutation attempt 410s on them.
     */
    public function testLapsedButNotYetSweptCartReturnsEmptyResult(): void
    {
        $cart = $this->openCart();
        $cart->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 1, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findActiveByBuyerAndEvent')->willReturn($cart);
        $this->events->expects(self::never())->method('getById');
        $this->clock->modify('+11 minutes'); // past the 10-minute TTL, sweep hasn't run

        $result = ($this->handler)($this->makeQuery(), $this->makeActor());

        self::assertEquals(CartResult::empty(), $result);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeQuery(): GetCartQuery
    {
        return new GetCartQuery(eventId: self::EVENT_ID);
    }

    private function makeActor(): UserContext
    {
        return new UserContext(UserId::fromString(self::BUYER_ID), Role::BUYER);
    }

    private function openCart(): Cart
    {
        return Cart::open(
            CartId::fromString(self::CART_ID),
            UserId::fromString(self::BUYER_ID),
            EventId::fromString(self::EVENT_ID),
            new \DateTimeImmutable(self::OPENED_AT),
        );
    }

    private function makeEvent(): Event
    {
        $event = Event::create(
            id: EventId::fromString(self::EVENT_ID),
            name: 'Test Event',
            venueId: VenueId::fromString('019e0000-0000-7000-8000-000000000099'),
            slug: EventSlug::create('test-event'),
            dateRange: DateRange::create(
                new \DateTimeImmutable('2026-06-01 19:00'),
                new \DateTimeImmutable('2026-06-01 22:00'),
            ),
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );

        $event->addTicketCategory(
            id: TicketCategoryId::fromString(self::CATEGORY_ID),
            name: 'VIP',
            price: new Money(2500, Currency::fromString('EUR')),
            totalQuantity: 100,
        );

        return $event;
    }
}
