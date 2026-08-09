<?php

declare(strict_types=1);

namespace Domain\Cart;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case CHECKED_OUT = 'checked_out';
    case EXPIRED = 'expired';
}
