<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

use App\Domain\Event\ValueObject\EventId;

final class RemoveTicketCategoryForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('Removing ticket categories from event "%s" is not allowed.', $id));
    }
}
