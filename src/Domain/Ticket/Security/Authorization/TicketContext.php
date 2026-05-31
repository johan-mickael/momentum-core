<?php

declare(strict_types=1);

namespace Domain\Ticket\Security\Authorization;

use Domain\Common\Security\Authorization\Subject;
use Domain\Ticket\Ticket;

final readonly class TicketContext implements Subject
{
    public function __construct(
        public Ticket $ticket,
    ) {
    }
}
