<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class UpdateCartItemForbidden extends CartException
{
    public static function create(): self
    {
        return new self('You are not allowed to modify this cart.');
    }
}
