<?php

declare(strict_types=1);

namespace Domain\Order\Security\Authorization;

use Domain\Common\Security\Authorization\Subject;
use Domain\Order\Order;

final readonly class OrderContext implements Subject
{
    public function __construct(
        public Order $order,
    ) {
    }
}
