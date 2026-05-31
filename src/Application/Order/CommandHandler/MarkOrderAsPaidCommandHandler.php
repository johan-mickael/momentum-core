<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Command\MarkOrderAsPaidCommand;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\ValueObject\OrderId;
use Domain\Order\ValueObject\PaymentReference;

final readonly class MarkOrderAsPaidCommandHandler implements MarkOrderAsPaidUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(MarkOrderAsPaidCommand $command): void
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));
        $order->markAsPaid(
            new \DateTimeImmutable($command->paidAt),
            null !== $command->paymentReference
                ? PaymentReference::fromString($command->paymentReference)
                : null,
        );
        $this->orders->save($order);
        $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
