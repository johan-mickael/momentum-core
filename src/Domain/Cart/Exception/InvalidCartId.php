<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class InvalidCartId extends CartException
{
    public static function invalidUuid(string $value): self
    {
        return new self(sprintf('Cart id "%s" is not a valid UUID.', $value));
    }
}
