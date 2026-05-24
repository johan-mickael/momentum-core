<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\EventId;

final class UpdateTicketCategoryForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('Updating ticket categories for event "%s" is not allowed.', $id));
    }
}
