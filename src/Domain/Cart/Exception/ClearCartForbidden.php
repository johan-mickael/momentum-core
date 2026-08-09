<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class ClearCartForbidden extends CartException
{
    public static function create(): self
    {
        return new self('You are not allowed to clear this cart.');
    }
}
