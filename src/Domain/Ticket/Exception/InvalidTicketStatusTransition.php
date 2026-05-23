<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Exception;

use App\Domain\Ticket\TicketStatus;
use DomainException;

final class InvalidTicketStatusTransition extends DomainException
{
    public static function cannotConfirm(TicketStatus $current): self
    {
        return new self(sprintf(
            'Cannot confirm a ticket with status "%s". Only reserved tickets can be confirmed.',
            $current->value,
        ));
    }

    public static function cannotCancel(TicketStatus $current): self
    {
        return new self(sprintf(
            'Cannot cancel a ticket with status "%s".',
            $current->value,
        ));
    }

    public static function cannotMarkAsUsed(TicketStatus $current): self
    {
        return new self(sprintf(
            'Cannot mark as used a ticket with status "%s". Only confirmed tickets can be used.',
            $current->value,
        ));
    }
}
