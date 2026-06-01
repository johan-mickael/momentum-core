<?php

declare(strict_types=1);

namespace Infrastructure\Stripe;

use Application\Order\Service\CheckoutSessionExpirerInterface;
use Stripe\StripeClient;

final readonly class StripeCheckoutSessionExpirer implements CheckoutSessionExpirerInterface
{
    public function __construct(
        private StripeClient $stripe,
    ) {
    }

    public function expire(string $sessionId): void
    {
        $this->stripe->checkout->sessions->expire($sessionId);
    }
}
