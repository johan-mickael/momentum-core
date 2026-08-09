<?php

declare(strict_types=1);

namespace Application\Cart\Command;

final readonly class CheckoutCartCommand
{
    public function __construct(
        public string $eventId,
    ) {
    }
}
