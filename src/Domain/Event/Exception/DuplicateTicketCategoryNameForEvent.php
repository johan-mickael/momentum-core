<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class DuplicateTicketCategoryNameForEvent extends \DomainException
{
    public static function withName(string $name): self
    {
        return new self("Duplicate ticket category name '{$name}'.");
    }
}
