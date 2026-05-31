<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class TicketCategoryCapacityInvalid extends \DomainException
{
    public static function create(int $given): self
    {
        return new self(
            sprintf(
                'Ticket category capacity must be at least 1, %d given.',
                $given,
            )
        );
    }
}
