<?php

declare(strict_types=1);

namespace App\Domain\Common\Exception;

final class NegativeMoneyAmount extends \DomainException
{
    public static function create(int $amount): self
    {
        return new self(
            sprintf(
                'Money amount must be non-negative, %d given.',
                $amount,
            )
        );
    }
}
