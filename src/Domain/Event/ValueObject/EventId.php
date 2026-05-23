<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

use App\Domain\Common\Id\IdInterface;
use App\Domain\Event\Exception\InvalidEventId;

final readonly class EventId implements IdInterface
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (!uuid_is_valid($value)) {
            throw InvalidEventId::invalidUuid($value);
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
