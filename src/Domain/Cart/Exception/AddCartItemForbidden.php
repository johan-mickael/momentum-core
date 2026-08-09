<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class AddCartItemForbidden extends CartException
{
    public static function create(): self
    {
        return new self('You are not allowed to add items to this cart.');
    }
}
