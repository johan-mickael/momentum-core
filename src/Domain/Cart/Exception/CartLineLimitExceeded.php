<?php

declare(strict_types=1);

namespace Domain\Cart\Exception;

final class CartLineLimitExceeded extends CartException
{
    public static function create(int $max): self
    {
        return new self(sprintf('A cart cannot hold more than %d distinct ticket categories.', $max));
    }
}
