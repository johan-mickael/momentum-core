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
        $candidates = $this->orders->findPendingExpiredBefore($now);

        if (empty($candidates)) {
            return;
        }

        $sessionIds = [];

        foreach ($candidates as $candidate) {
            $sessionId = $this->transactionManager->transactional(function () use ($candidate, $now) {
                // Re-read under lock: a payment webhook may have paid this
                // order since findPendingExpiredBefore's unlocked SELECT.
                $order = $this->orders->getByIdForUpdate($candidate->id());
                $order->expire($now);
                $events = $order->pullRecordedEvents();

                if (empty($events)) {
                    // expire() no-op'd — order is no longer PENDING_PAYMENT.
                    return null;
                }

                $this->orders->save($order);
                $this->dispatcher->dispatch(...$events);

                return $order->checkoutSessionId();
            });

            if (null !== $sessionId) {
                $sessionIds[] = $sessionId;
            }
        }

        foreach ($sessionIds as $sessionId) {
            try {
                $this->sessionExpirer->expire($sessionId);
            } catch (\Throwable) {
                // session may already be expired, paid, or not found — non-critical
            }
        }
    }
}
