<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Common\Event\DomainEventDispatcherInterface;
use Application\Order\Service\CheckoutSessionExpirerInterface;
use Domain\Common\Transaction\TransactionManagerInterface;
use Domain\Order\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ExpireOrdersCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private DomainEventDispatcherInterface $dispatcher,
        private ClockInterface $clock,
        private TransactionManagerInterface $transactionManager,
        private CheckoutSessionExpirerInterface $sessionExpirer,
    ) {
    }

    public function __invoke(): void
    {
        $now = $this->clock->now();
        $expired = $this->orders->findPendingExpiredBefore($now);

        if (empty($expired)) {
            return;
        }

        $sessionIds = [];

        foreach ($expired as $order) {
            $sessionId = $order->checkoutSessionId();
            if (null !== $sessionId) {
                $sessionIds[] = $sessionId;
            }

            $order->expire($now);
            $this->orders->save($order);
            $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        }

        $this->transactionManager->flush();

        foreach ($sessionIds as $sessionId) {
            try {
                $this->sessionExpirer->expire($sessionId);
            } catch (\Throwable) {
                // session may already be expired, paid, or not found — non-critical
            }
        }
    }
}
