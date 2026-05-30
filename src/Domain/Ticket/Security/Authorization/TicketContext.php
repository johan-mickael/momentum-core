<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Security\Authorization;

use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Ticket\Ticket;

final readonly class TicketContext implements Subject
{
    public function __construct(
        public Ticket $ticket,
    ) {
    }
}
