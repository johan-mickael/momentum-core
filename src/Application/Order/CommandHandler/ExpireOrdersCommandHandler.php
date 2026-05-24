<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Ticket\TicketRepositoryInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class ExpireOrdersCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private TicketRepositoryInterface $tickets,
        private EventRepositoryInterface $events,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(): void
    {
        $now = $this->clock->now();
        $expired = $this->orders->findPendingExpiredBefore($now);

        if (empty($expired)) {
            return;
        }

        foreach ($expired as $order) {
            $this->expireOrder($order, $now);
        }

        $this->transactionManager->flush();
    }

    private function expireOrder(Order $order, DateTimeImmutable $now): void
    {
        $order->expire($now);

        $event = $this->events->getById($order->eventId());

        foreach ($order->items() as $item) {
            $ticket = $this->tickets->findById($item->ticketId());
            $ticket->cancel();
            $event->releaseTicketInCategory($item->categoryId());
            $this->tickets->save($ticket);
        }

        $this->events->save($event);
        $this->orders->save($order);
    }
}
