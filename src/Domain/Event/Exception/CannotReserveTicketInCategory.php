<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class CannotReserveTicketInCategory extends \DomainException
{
    public static function noAvailability(string $categoryName): self
    {
        return new self(sprintf(
            'No tickets available in category "%s".',
            $categoryName,
        ));
    }
}
