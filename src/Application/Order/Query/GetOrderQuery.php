<?php

declare(strict_types=1);

namespace Application\Order\Query;

final class GetOrderQuery
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}
