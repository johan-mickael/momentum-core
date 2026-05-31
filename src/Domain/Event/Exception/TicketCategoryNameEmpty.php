<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class TicketCategoryNameEmpty extends \DomainException
{
    public static function create(): self
    {
        return new self('Ticket category name must not be empty.');
    }
}
