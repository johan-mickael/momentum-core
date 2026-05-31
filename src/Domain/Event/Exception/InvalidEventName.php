<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class InvalidEventName extends \InvalidArgumentException
{
    public static function create(): self
    {
        return new self('Event name cannot be empty.');
    }
}
