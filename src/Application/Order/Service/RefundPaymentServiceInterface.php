<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use App\Domain\Order\ValueObject\PaymentReference;

interface RefundPaymentServiceInterface
{
    public function refund(PaymentReference $paymentReference): void;
}
