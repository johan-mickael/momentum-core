<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\ValueObject\EventId;

final class RemoveTicketCategoryForbidden extends EventException
{
    public static function forEvent(EventId $id): self
    {
        return new self(sprintf('Removing ticket categories from event "%s" is not allowed.', $id));
    }
}
