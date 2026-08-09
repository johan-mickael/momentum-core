<?php

declare(strict_types=1);

namespace Domain\Cart\ValueObject;

use Domain\Cart\Exception\InvalidCartId;

final readonly class CartId
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!uuid_is_valid($value)) {
            throw InvalidCartId::invalidUuid($value);
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

    public function __toString(): string
    {
        return $this->value;
    }
}
