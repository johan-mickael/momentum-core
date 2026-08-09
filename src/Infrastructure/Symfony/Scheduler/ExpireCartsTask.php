<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Scheduler;

use Application\Cart\CommandHandler\ExpireCartsCommandHandler;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

/**
 * Runs every 30 seconds to release the holds of active carts whose TTL has
 * lapsed. Tighter than ExpireOrdersTask's 60s since the cart TTL (10 min) is
 * shorter than the order payment TTL (15 min) and abandoned carts should
 * free up inventory promptly.
 */
#[AsPeriodicTask(frequency: '30 seconds', jitter: 5)]
final readonly class ExpireCartsTask
{
    public function __construct(
        private ExpireCartsCommandHandler $handler,
    ) {
    }

    public function __invoke(): void
    {
        ($this->handler)();
    }
}
