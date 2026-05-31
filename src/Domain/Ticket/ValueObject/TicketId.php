<?php

declare(strict_types=1);

namespace Domain\Ticket\ValueObject;

use Domain\Ticket\Exception\InvalidTicketId;

final class TicketId
{
    private function __construct(
        private readonly string $value,
    ) {
        if ('' === trim($value)) {
            throw InvalidTicketId::empty();
        }
    }

    public static function fromString(string $value): self
    {
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
