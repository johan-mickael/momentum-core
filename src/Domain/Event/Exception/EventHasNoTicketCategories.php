<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class EventHasNoTicketCategories extends EventException
{
    public static function create(): self
    {
        return new self('Cannot publish an event that has no ticket categories.');
    }
}
