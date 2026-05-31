<?php

declare(strict_types=1);

namespace Domain\Ticket\Exception;

final class InvalidEntryCode extends \DomainException
{
    public static function empty(): self
    {
        return new self('Entry code cannot be empty');
    }
}
