<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cart\CommandHandler;

use Application\Cart\CartResultAssembler;
use Application\Cart\Command\AddCartItemCommand;
use Application\Cart\CommandHandler\AddCartItemCommandHandler;
use Application\Cart\CommandHandler\AddCartItemUseCase;
use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\CartStatus;
use Domain\Cart\Exception\AddCartItemForbidden;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
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

final class AddCartItemCommandHandlerTest extends TestCase
{
    private const string BUYER_ID = '019e0000-0000-7000-8000-000000000001';
    private const string EVENT_ID = '019e0000-0000-7000-8000-000000000002';
    private const string STALE_CART_ID = '019e0000-0000-7000-8000-000000000003';
    private const string NEW_CART_ID = '019e0000-0000-7000-8000-000000000004';
    private const string CATEGORY_ID = '019e0000-0000-7000-8000-000000000005';
    private const string OPENED_AT = '2040-01-01 10:00:00';

    private CartRepositoryInterface&MockObject $carts;
    private EventRepositoryInterface&MockObject $events;
    private AuthorizationServiceInterface&MockObject $auth;
    private DomainEventDispatcherInterface&MockObject $dispatcher;
    private IdGeneratorInterface&MockObject $idGenerator;
    private MockClock $clock;
    private AddCartItemCommandHandler $handler;

    protected function setUp(): void
    {
        $this->carts = $this->createMock(CartRepositoryInterface::class);
        $this->events = $this->createMock(EventRepositoryInterface::class);
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->auth->method('authorize')->willReturn(true);
        $this->dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);
        $this->idGenerator->method('generate')->willReturn(self::NEW_CART_ID);
        $this->clock = new MockClock(self::OPENED_AT);

        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $operation) => $operation());

        $this->handler = new AddCartItemCommandHandler(
            $this->events,
            $this->carts,
            $this->auth,
            $this->dispatcher,
            new CartResultAssembler(),
            $this->idGenerator,
            $this->clock,
            $transactionManager,
        );
    }

    public function testImplementsUseCase(): void
    {
        self::assertInstanceOf(AddCartItemUseCase::class, $this->handler);
    }

    public function testUnauthorizedActorThrowsAddCartItemForbidden(): void
    {
        $this->auth = $this->createMock(AuthorizationServiceInterface::class);
        $this->auth->method('authorize')->willReturn(false);
        $this->handler = new AddCartItemCommandHandler(
            $this->events,
            $this->carts,
            $this->auth,
            $this->dispatcher,
            new CartResultAssembler(),
            $this->idGenerator,
            $this->clock,
            $this->makeTransactionManager(),
        );
        $this->carts->expects(self::never())->method('findActiveByBuyerAndEventForUpdate');

        $this->expectException(AddCartItemForbidden::class);

        ($this->handler)($this->makeCommand(2), $this->makeActor());
    }

    public function testOpensAFreshCartWhenNoneExists(): void
    {
        $event = $this->makePublishedEvent(totalQuantity: 10);
        $this->events->method('getByIdForUpdate')->willReturn($event);
        $this->carts->method('findActiveByBuyerAndEventForUpdate')->willReturn(null);

        $saved = [];
        $this->carts->method('save')->willReturnCallback(function (Cart $cart) use (&$saved) {
            $saved[] = $cart;
        });

        $result = ($this->handler)($this->makeCommand(3), $this->makeActor());

        self::assertSame(self::NEW_CART_ID, $result->id);
        self::assertCount(1, $result->items);
        self::assertSame(3, $result->items[0]->quantity);
        self::assertCount(1, $saved);
        self::assertSame(CartStatus::ACTIVE, $saved[0]->status());
        self::assertSame(7, $event->categoryById(TicketCategoryId::fromString(self::CATEGORY_ID))->availableQuantity());
    }

    public function testAddsToTheExistingActiveCart(): void
    {
        $event = $this->makePublishedEvent(totalQuantity: 10);
        $event->reserveTicketInCategory(TicketCategoryId::fromString(self::CATEGORY_ID)); // simulates 1 already held
        $this->events->method('getByIdForUpdate')->willReturn($event);

        $existing = $this->openCart(self::STALE_CART_ID);
        $existing->addItem(TicketCategoryId::fromString(self::CATEGORY_ID), 1, new \DateTimeImmutable(self::OPENED_AT));
        $this->carts->method('findActiveByBuyerAndEventForUpdate')->willReturn($existing);

        $this->carts->expects(self::once())->method('save')->with($existing);

        $result = ($this->handler)($this->makeCommand(2), $this->makeActor());

        self::assertSame(self::STALE_CART_ID, $result->id);
        self::assertSame(3, $result->items[0]->quantity); // 1 already in cart + 2 just added
        self::assertSame(7, $event->categoryById(TicketCategoryId::fromString(self::CATEGORY_ID))->availableQuantity());
    }

    /**
     * The periodic sweep (ExpireCartsTask) runs at most every 30s — a
     * lapsed cart can still be sitting here as status=ACTIVE. Before the
     * fix, findActiveByBuyerAndEventForUpdate() would return that stale
     * row, and addItem() would throw CartAlreadyExpired on it — "add to
     * cart" must always work, so the handler now releases the stale hold,
     * expires that cart in place, and opens a fresh one instead.
     */
    public function testLapsedButNotYetSweptCartIsExpiredAndAFreshCartIsOpened(): void
    {
        $categoryId = TicketCategoryId::fromString(self::CATEGORY_ID);
        $event = $this->makePublishedEvent(totalQuantity: 10);
        $event->reserveTicketInCategory($categoryId);
        $event->reserveTicketInCategory($categoryId); // 2 tickets held by the about-to-be-stale cart
        self::assertSame(8, $event->categoryById($categoryId)->availableQuantity());
        $this->events->method('getByIdForUpdate')->willReturn($event);

        $staleCart = $this->openCart(self::STALE_CART_ID);
        $staleCart->addItem($categoryId, 2, new \DateTimeImmutable(self::OPENED_AT));
        $this->carts->method('findActiveByBuyerAndEventForUpdate')->willReturn($staleCart);

        $this->clock->modify('+11 minutes'); // past the 10-minute TTL, sweep hasn't run

        $savedCarts = [];
        $this->carts->method('save')->willReturnCallback(function (Cart $cart) use (&$savedCarts) {
            $savedCarts[] = $cart;
        });
        $dispatchedBatches = [];
        $this->dispatcher->method('dispatch')->willReturnCallback(function (...$events) use (&$dispatchedBatches) {
            $dispatchedBatches[] = $events;
        });

        $result = ($this->handler)($this->makeCommand(3), $this->makeActor());

        // The stale cart was expired and persisted in its own right...
        self::assertCount(2, $savedCarts);
        self::assertSame(self::STALE_CART_ID, $savedCarts[0]->id()->toString());
        self::assertSame(CartStatus::EXPIRED, $savedCarts[0]->status());

        // ...and a brand new cart holds the requested item, not the stale one.
        self::assertSame(self::NEW_CART_ID, $savedCarts[1]->id()->toString());
        self::assertSame(CartStatus::ACTIVE, $savedCarts[1]->status());
        self::assertSame(self::NEW_CART_ID, $result->id);
        self::assertCount(1, $result->items);
        self::assertSame(3, $result->items[0]->quantity);

        // Both the stale cart's release and the new reservation happened:
        // 8 available -> +2 released -> 10 -> -3 reserved -> 7.
        self::assertSame(7, $event->categoryById($categoryId)->availableQuantity());

        // Events for both carts were dispatched, not just the new one.
        self::assertCount(2, $dispatchedBatches);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeTransactionManager(): TransactionManagerInterface&MockObject
    {
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $operation) => $operation());

        return $transactionManager;
    }

    private function makeCommand(int $quantity): AddCartItemCommand
    {
        return new AddCartItemCommand(eventId: self::EVENT_ID, categoryId: self::CATEGORY_ID, quantity: $quantity);
    }

    private function makeActor(): UserContext
    {
        return new UserContext(UserId::fromString(self::BUYER_ID), Role::BUYER);
    }

    private function openCart(string $cartId): Cart
    {
        return Cart::open(
            CartId::fromString($cartId),
            UserId::fromString(self::BUYER_ID),
            EventId::fromString(self::EVENT_ID),
            new \DateTimeImmutable(self::OPENED_AT),
        );
    }

    private function makePublishedEvent(int $totalQuantity): Event
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
            totalQuantity: $totalQuantity,
        );

        $event->publish();

        return $event;
    }
}
