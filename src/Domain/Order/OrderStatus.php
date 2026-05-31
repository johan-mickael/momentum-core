<?php

declare(strict_types=1);

namespace Domain\Order;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
