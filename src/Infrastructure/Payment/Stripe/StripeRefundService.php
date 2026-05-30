<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\Stripe;

use App\Application\Order\Service\RefundPaymentServiceInterface;
use App\Domain\Order\ValueObject\PaymentReference;
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
