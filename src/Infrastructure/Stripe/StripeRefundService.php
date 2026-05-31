<?php

declare(strict_types=1);

namespace Infrastructure\Stripe;

use Application\Order\Service\RefundPaymentServiceInterface;
use Domain\Order\ValueObject\PaymentReference;
use Stripe\StripeClient;

final readonly class StripeRefundService implements RefundPaymentServiceInterface
{
    public function __construct(
        private StripeClient $stripe,
    ) {
    }

    public function refund(PaymentReference $paymentReference): void
    {
        $this->stripe->refunds->create([
            'payment_intent' => $paymentReference->toString(),
        ]);
    }
}
