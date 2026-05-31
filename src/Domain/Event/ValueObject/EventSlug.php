<?php

declare(strict_types=1);

namespace Domain\Event\ValueObject;

use Domain\Event\Exception\InvalidEventSlug;

final readonly class EventSlug
{
    public const string EVENT_SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(private string $value)
    {
    }

    public static function create(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if (!preg_match(self::EVENT_SLUG_PATTERN, $normalized)) {
            throw InvalidEventSlug::create($value);
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
