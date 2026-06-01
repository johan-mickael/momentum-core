<?php

declare(strict_types=1);

namespace Application\Order\Service;

final readonly class CheckoutSessionData
{
    public function __construct(
        public string $url,
        public string $sessionId,
    ) {
    }
}
