<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class RemoveCartItemForbidden extends CartException
{
    public static function create(): self
    {
        return new self('You are not allowed to remove items from this cart.');
    }
}
