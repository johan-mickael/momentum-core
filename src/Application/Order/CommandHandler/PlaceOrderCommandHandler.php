<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\Result\OrderItemResult;
use App\Application\Order\Result\PlaceOrderResult;
use App\Domain\Common\Id\IdGeneratorInterface;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Order\Exception\PlaceOrderForbidden;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

final readonly class PlaceOrderCommandHandler implements PlaceOrderUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private TicketRepositoryInterface $tickets,
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
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

            for ($i = 0; $i < $quantity; $i++) {
                $event->reserveTicketInCategory($categoryId);

                $ticket = Ticket::reserve(
                    id: TicketId::fromString($this->idGenerator->generate()),
                    eventId: $eventId,
                    categoryId: $categoryId,
                    holderId: $actor->id,
                    price: $category->price(),
                    reservedAt: $now,
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
        $this->transactionManager->flush();

        return new PlaceOrderResult(
            id: $order->id()->toString(),
            status: $order->status()->value,
            totalAmount: $order->total()->amount(),
            currency: $order->total()->currency()->toString(),
            expiresAt: $order->expiresAt()->format(DateTimeInterface::ATOM),
            items: array_map(
                static fn(OrderItem $item) => new OrderItemResult(
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
