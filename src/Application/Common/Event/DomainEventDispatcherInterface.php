<?php

declare(strict_types=1);

namespace Application\Common\Event;

use Domain\Common\Event\DomainEventInterface;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEventInterface ...$events): void;
}
