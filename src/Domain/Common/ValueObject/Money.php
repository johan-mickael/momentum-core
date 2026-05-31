<?php

declare(strict_types=1);

namespace Domain\Common\ValueObject;

use Domain\Common\Exception\CurrencyMismatch;
use Domain\Common\Exception\NegativeMoneyAmount;

final readonly class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly Currency $currency,
    ) {
        if ($amount < 0) {
            throw NegativeMoneyAmount::create($amount);
        }
    }

    public function add(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency->equals($other->currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }
}
