<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Command\PlaceOrderCommand;
use Application\Order\Result\OrderItemResult;
use Application\Order\Result\PlaceOrderResult;
use Domain\Common\Id\IdGeneratorInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Order\Exception\PlaceOrderForbidden;
use Domain\Order\Order;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\ValueObject\OrderId;
use Domain\Ticket\Ticket;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Psr\Clock\ClockInterface;

final readonly class PlaceOrderCommandHandler implements PlaceOrderUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private TicketRepositoryInterface $tickets,
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(PlaceOrderCommand $command, UserContext $actor): PlaceOrderResult
    {
        if (!$this->authorizationService->authorize($actor, Action::CREATE, Order::class)) {
            throw PlaceOrderForbidden::create();
        }

        $eventId = EventId::fromString($command->eventId);
        $event = $this->events->getById($eventId);
        $now = $this->clock->now();

        $orderItems = [];
        $newTickets = [];

        foreach ($command->items as ['categoryId' => $rawCategoryId, 'quantity' => $quantity]) {
            $categoryId = TicketCategoryId::fromString($rawCategoryId);
            $category = $event->categoryById($categoryId);

            for ($i = 0; $i < $quantity; ++$i) {
                $event->reserveTicketInCategory($categoryId);

                $ticket = Ticket::reserve(
                    id: TicketId::fromString($this->idGenerator->generate()),
                    eventId: $eventId,
                    categoryId: $categoryId,
                    holderId: $actor->id,
                    price: $category->price(),
                    reservedAt: $now,
                    entryCode: EntryCode::fromString($this->idGenerator->generate()),
                );

                $orderItems[] = new OrderItem($categoryId, $ticket->id(), $category->price());
                $newTickets[] = $ticket;
            }
        }

        $order = Order::place(
            id: OrderId::fromString($this->idGenerator->generate()),
            buyerId: $actor->id,
            eventId: $eventId,
            items: $orderItems,
            placedAt: $now,
        );

        $this->events->save($event);
        foreach ($newTickets as $ticket) {
            $this->tickets->save($ticket);
        }
        $this->orders->save($order);
        $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        foreach ($newTickets as $ticket) {
            $this->dispatcher->dispatch(...$ticket->pullRecordedEvents());
        }
        $this->transactionManager->flush();

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
    }
}
