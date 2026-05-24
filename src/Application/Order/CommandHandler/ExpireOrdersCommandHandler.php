<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Order\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ExpireOrdersCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private DomainEventDispatcherInterface $dispatcher,
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
            $order->expire($now);
            $this->orders->save($order);
            $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        }

        $this->transactionManager->flush();
    }
}
