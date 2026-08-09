<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class EmptyCart extends CartException
{
    public static function create(): self
    {
        return new self('Cannot check out an empty cart.');
    }
}
