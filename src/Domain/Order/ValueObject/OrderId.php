<?php

declare(strict_types=1);

namespace Domain\Order\ValueObject;

use Domain\Order\Exception\InvalidOrderId;

final readonly class OrderId
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!uuid_is_valid($value)) {
            throw InvalidOrderId::invalidUuid($value);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
