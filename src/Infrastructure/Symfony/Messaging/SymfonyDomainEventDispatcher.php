<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Messaging;

use Application\Common\Event\DomainEventDispatcherInterface;
use Domain\Common\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class SymfonyDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function dispatch(DomainEventInterface ...$events): void
    {
        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
