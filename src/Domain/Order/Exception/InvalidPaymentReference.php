<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class InvalidPaymentReference extends \DomainException
{
    public static function empty(): self
    {
        return new self('Payment reference cannot be empty');
    }
}
