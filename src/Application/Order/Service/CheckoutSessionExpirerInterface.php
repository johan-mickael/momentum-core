<?php

declare(strict_types=1);

namespace Application\Order\Service;

interface CheckoutSessionExpirerInterface
{
    public function expire(string $sessionId): void;
}
