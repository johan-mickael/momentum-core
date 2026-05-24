<?php

namespace App\Domain\Event\Exception;
use DomainException;

final class DuplicateTicketCategoryNameForEvent extends DomainException
{
    public static function withName(string $name): self
    {
        return new self("Duplicate ticket category name '{$name}'.");
    }
}
