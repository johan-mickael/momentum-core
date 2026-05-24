<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\MarkOrderAsPaidCommand;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Ticket\TicketRepositoryInterface;
use DateTimeImmutable;

final readonly class MarkOrderAsPaidCommandHandler implements MarkOrderAsPaidUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private TicketRepositoryInterface $tickets,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(MarkOrderAsPaidCommand $command, UserContext $actor): void
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        $order->markAsPaid(new DateTimeImmutable($command->paidAt));

        foreach ($order->items() as $item) {
            $ticket = $this->tickets->findById($item->ticketId());
            $ticket->confirm();
            $this->tickets->save($ticket);
        }

        $this->orders->save($order);
        $this->transactionManager->flush();
    }
}
