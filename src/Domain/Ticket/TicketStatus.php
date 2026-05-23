<?php

declare(strict_types=1);

namespace App\Domain\Ticket;

enum TicketStatus: string
{
    case RESERVED  = 'reserved';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case USED      = 'used';
}
