<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\Common\Id\IdInterface;
use Domain\User\Exception\InvalidUserId;

final readonly class UserId implements IdInterface
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!uuid_is_valid($value)) {
            throw new InvalidUserId($value);
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
