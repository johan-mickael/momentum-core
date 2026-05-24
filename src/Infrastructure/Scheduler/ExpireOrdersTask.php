<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler;

use App\Application\Order\CommandHandler\ExpireOrdersCommandHandler;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

/**
 * Runs every 60 seconds to cancel pending orders whose TTL has expired
 * and release the reserved ticket slots back to each category.
 */
#[AsPeriodicTask(frequency: '60 seconds', jitter: 5)]
final readonly class ExpireOrdersTask
{
    public function __construct(
        private ExpireOrdersCommandHandler $handler,
    ) {}

    public function __invoke(): void
    {
        ($this->handler)();
    }
}
