<?php

declare(strict_types=1);

namespace Domain\Ticket\ValueObject;

use Domain\Ticket\Exception\InvalidEntryCode;

final class EntryCode
{
    private function __construct(
        private readonly string $value,
    ) {
        if ('' === trim($value)) {
            throw InvalidEntryCode::empty();
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
