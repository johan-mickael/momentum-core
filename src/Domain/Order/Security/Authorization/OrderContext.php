<?php

declare(strict_types=1);

namespace App\Domain\Order\Security\Authorization;

use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Order\Order;

final readonly class OrderContext implements Subject
{
    public function __construct(
        public Order $order,
    ) {
    }
}
