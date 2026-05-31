<?php

declare(strict_types=1);

namespace Domain\Common\ValueObject;

use Domain\Common\Exception\InvalidCurrency;

final readonly class Currency
{
    private function __construct(
        private string $code,
    ) {
        if ('' === trim($code)) {
            throw InvalidCurrency::emptyCode();
        }
    }

    public static function fromString(string $code): self
    {
        return new self(strtoupper(trim($code)));
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function toString(): string
    {
        return $this->code;
    }
}
