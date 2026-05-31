<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

final class InvalidEventSlug extends EventException
{
    public static function create(string $value): self
    {
        return new self(
            "'{$value}' is not a valid event slug. Must be lowercase alphanumeric with hyphens only."
        );
    }
}
