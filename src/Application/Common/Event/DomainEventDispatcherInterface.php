<?php

declare(strict_types=1);

namespace App\Application\Common\Event;

use App\Domain\Common\Event\DomainEventInterface;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEventInterface ...$events): void;
}
