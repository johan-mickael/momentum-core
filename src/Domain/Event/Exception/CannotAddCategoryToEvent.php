<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\EventStatus;

final class CannotAddCategoryToEvent extends \DomainException
{
    public static function eventIsNotActive(EventStatus $status): self
    {
        return new self(sprintf(
            'Cannot add a ticket category to an event with status "%s".',
            $status->value,
        ));
    }
}
