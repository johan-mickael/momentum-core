<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\Common\Id\IdInterface;
use App\Domain\User\Exception\InvalidUserId;

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
