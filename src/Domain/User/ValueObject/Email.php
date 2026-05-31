<?php

declare(strict_types=1);

namespace Domain\User\ValueObject;

use Domain\User\Exception\InvalidEmail;

final class Email
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function create(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::create($value);
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
