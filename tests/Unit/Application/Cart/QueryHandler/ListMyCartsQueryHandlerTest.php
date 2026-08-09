<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cart\QueryHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Query\ListMyCartsQuery;
use Application\Cart\QueryHandler\ListMyCartsQueryHandler;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\ListCartsForbidden;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Security\Authorization\Action;
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

final class ListMyCartsQueryHandlerTest extends TestCase
{
    private const string BUYER_ID = '019e0000-0000-7000-8000-000000000001';
    private const string EVENT_A_ID = '019e0000-0000-7000-8000-000000000002';
    private const string EVENT_B_ID = '019e0000-0000-7000-8000-000000000003';
    private const string CART_A_ID = '019e0000-0000-7000-8000-000000000004';
    private const string CART_B_ID = '019e0000-0000-7000-8000-000000000005';
    private const string CATEGORY_ID = '019e0000-0000-7000-8000-000000000006';
    private const string OPENED_AT = '2040-01-01 10:00:00';

    private CartRepositoryInterface&MockObject $carts;
    private EventRepositoryInterface&MockObject $events;
    private AuthorizationServiceInterface&MockObject $auth;
    private MockClock $clock;
    private ListMyCartsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->carts = $this->createMock(CartRepositoryInterface::class);
        $this->events = $this->createMock(EventRepositoryInterface::class);
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->clock = new MockClock(self::OPENED_AT);

        $this->handler = new ListMyCartsQueryHandler($this->carts, $this->events, $this->auth, new CartResultAssembler(), $this->clock);
    }

    public function testUnauthorizedActorThrowsListCartsForbidden(): void
    {
        $this->auth->method('authorize')->willReturn(false);
        $this->carts->expects(self::never())->method('findAllActiveByBuyer');

        $this->expectException(ListCartsForbidden::class);

        ($this->handler)(new ListMyCartsQuery(), $this->makeActor());
    }

    public function testAuthorizationUsesListActionOnCartClass(): void
    {
        $actor = $this->makeActor();

        $this->auth->expects(self::once())->method('authorize')
            ->with($actor, Action::LIST, Cart::class)
            ->willReturn(true);
        $this->carts->method('findAllActiveByBuyer')->willReturn([]);

        ($this->handler)(new ListMyCartsQuery(), $actor);
    }

    /**
     * Removing a cart's last line leaves it ACTIVE but item-less — only
     * checkout()/expire() ever change status, so findAllActiveByBuyer() can
     * legitimately return one. It must not reach the assembler (which would
     * serialize it with currency: '' and crash the frontend), and it has
     * nothing worth showing the buyer in a cart list anyway.
     */
    public function testFiltersOutActiveButItemLessCarts(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $emptyCart = $this->openCart(self::CART_A_ID, self::EVENT_A_ID);
        $cartWithItems = $this->openCart(self::CART_B_ID, self::EVENT_B_ID);
        $cartWithItems->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 1, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findAllActiveByBuyer')->willReturn([$emptyCart, $cartWithItems]);
        $this->events->method('getById')->willReturn($this->makeEvent(self::EVENT_B_ID));

        $result = ($this->handler)(new ListMyCartsQuery(), $this->makeActor());

        self::assertCount(1, $result);
        self::assertSame(self::EVENT_B_ID, $result[0]->eventId);
    }

    /**
     * The periodic sweep (ExpireCartsTask) only runs every 30s — a lapsed
     * cart sits ACTIVE in the DB until then. It must not show up in "my
     * carts" during that window, and must not reach the assembler (which
     * would still serialize it — real items, real currency — as if it were
     * perfectly usable).
     */
    public function testFiltersOutLapsedButNotYetSweptCarts(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $cart = $this->openCart(self::CART_A_ID, self::EVENT_A_ID);
        $cart->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 1, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findAllActiveByBuyer')->willReturn([$cart]);
        $this->events->expects(self::never())->method('getById');
        $this->clock->modify('+11 minutes'); // past the 10-minute TTL, sweep hasn't run

        $result = ($this->handler)(new ListMyCartsQuery(), $this->makeActor());

        self::assertSame([], $result);
    }

    public function testReturnsOneSummaryPerCartWithEventNameAndTotals(): void
    {
        $this->auth->method('authorize')->willReturn(true);

        $cart = $this->openCart(self::CART_A_ID, self::EVENT_A_ID);
        $cart->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 3, new \DateTimeImmutable(self::OPENED_AT));

        $this->carts->method('findAllActiveByBuyer')->willReturn([$cart]);
        $this->events->method('getById')->willReturn($this->makeEvent(self::EVENT_A_ID));

        $result = ($this->handler)(new ListMyCartsQuery(), $this->makeActor());

        self::assertCount(1, $result);
        self::assertSame(self::EVENT_A_ID, $result[0]->eventId);
        self::assertSame('Test Event', $result[0]->eventName);
        self::assertSame(7500, $result[0]->cart->totalAmount);
        self::assertSame('EUR', $result[0]->cart->currency);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeActor(): UserContext
    {
        return new UserContext(UserId::fromString(self::BUYER_ID), Role::BUYER);
    }

    private function openCart(string $cartId, string $eventId): Cart
    {
        return Cart::open(
            CartId::fromString($cartId),
            UserId::fromString(self::BUYER_ID),
            EventId::fromString($eventId),
            new \DateTimeImmutable(self::OPENED_AT),
        );
    }

    private function makeEvent(string $eventId): Event
    {
        $event = Event::create(
            id: EventId::fromString($eventId),
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
