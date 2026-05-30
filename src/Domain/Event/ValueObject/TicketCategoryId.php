<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

use App\Domain\Event\Exception\InvalidTicketCategoryId;

final readonly class TicketCategoryId
{
    private function __construct(
        private string $value,
    ) {
        if ('' === trim($value)) {
            throw InvalidTicketCategoryId::empty();
        }

        if (!uuid_is_valid($value)) {
            throw InvalidTicketCategoryId::invalidUuid($value);
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
