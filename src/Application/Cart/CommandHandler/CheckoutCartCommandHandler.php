<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\CheckoutCartCommand;
use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Result\OrderItemResult;
use Application\Order\Result\PlaceOrderResult;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\Exception\CartNotFound;
use Domain\Cart\Exception\CheckoutCartForbidden;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Order\Order;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\ValueObject\OrderId;
use Domain\Ticket\Ticket;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Psr\Clock\ClockInterface;

final readonly class CheckoutCartCommandHandler implements CheckoutCartUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private CartRepositoryInterface $carts,
        private TicketRepositoryInterface $tickets,
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CheckoutCartCommand $command, UserContext $actor): PlaceOrderResult
    {
        $eventId = EventId::fromString($command->eventId);
        $now = $this->clock->now();

        return $this->transactionManager->transactional(function () use ($eventId, $actor, $now) {
            $cart = $this->carts->findActiveByBuyerAndEventForUpdate($actor->id, $eventId)
                ?? throw CartNotFound::forBuyerAndEvent($actor->id, $eventId);

            if (!$this->authorizationService->authorize($actor, Action::CHECKOUT, new CartContext($cart))) {
                throw CheckoutCartForbidden::create();
            }

            $event = $this->events->getByIdForUpdate($eventId);

            // Holds already exist on the category from the cart's add/update
            // calls — checkout mints tickets against them, it must NEVER
            // call reserveTicketInCategory again (that would double-count).
            $orderItems = [];
            $newTickets = [];
            foreach ($cart->items() as $item) {
                $category = $event->categoryById($item->categoryId());

                for ($i = 0; $i < $item->quantity(); ++$i) {
                    $ticket = Ticket::reserve(
                        id: TicketId::fromString($this->idGenerator->generate()),
                        eventId: $eventId,
                        categoryId: $item->categoryId(),
                        holderId: $actor->id,
                        price: $category->price(),
                        reservedAt: $now,
                        entryCode: EntryCode::fromString($this->idGenerator->generate()),
                    );

                    $orderItems[] = new OrderItem($item->categoryId(), $ticket->id(), $category->price(), $category->name());
                    $newTickets[] = $ticket;
                }
            }

            // checkout() throws EmptyCart / CartAlreadyExpired — validated
            // AFTER minting so those guards run against the cart's own
            // invariants rather than being duplicated here.
            $cart->checkout($now);

            $order = Order::place(
                id: OrderId::fromString($this->idGenerator->generate()),
                buyerId: $actor->id,
                eventId: $eventId,
                items: $orderItems,
                placedAt: $now,
            );

            $this->carts->save($cart);
            $this->orders->save($order);
            foreach ($newTickets as $ticket) {
                $this->tickets->save($ticket);
            }

            $this->dispatcher->dispatch(...$cart->pullRecordedEvents());
            $this->dispatcher->dispatch(...$order->pullRecordedEvents());
            foreach ($newTickets as $ticket) {
                $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
            }

            return new PlaceOrderResult(
                id: $order->id()->toString(),
                status: $order->status()->value,
                totalAmount: $order->total()->amount(),
                currency: $order->total()->currency()->toString(),
                expiresAt: $order->expiresAt()->format(\DateTimeInterface::ATOM),
                items: array_map(
                    static fn (OrderItem $item) => new OrderItemResult(
                        categoryId: $item->categoryId()->toString(),
                        ticketId: $item->ticketId()->toString(),
                        unitPriceAmount: $item->unitPrice()->amount(),
                        unitPriceCurrency: $item->unitPrice()->currency()->toString(),
                    ),
                    $order->items(),
                ),
            );
        });
    }
}
