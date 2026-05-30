<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

use App\Domain\Order\Exception\InvalidPaymentReference;

final class PaymentReference
{
    private function __construct(
        private readonly string $value,
    ) {
        if ('' === trim($value)) {
            throw InvalidPaymentReference::empty();
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
