<?php

declare(strict_types=1);

namespace Application\Order\Service;

use Domain\Order\ValueObject\PaymentReference;

interface RefundPaymentServiceInterface
{
    public function refund(PaymentReference $paymentReference): void;
}
