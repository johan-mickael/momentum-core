<?php

declare(strict_types=1);

namespace Domain\Ticket\Security\Authorization;

use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\Subject;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Ticket\Ticket;

final class TicketPolicy implements PolicyInterface
{
    public function supports(Subject|string $subject): bool
    {
        return Ticket::class === $subject || $subject instanceof TicketContext;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if (Ticket::class === $subject && Action::CREATE === $action) {
            return true;
        }

        if (Ticket::class === $subject && Action::VIEW === $action) {
            return true;
        }

        if (Ticket::class === $subject && Action::LIST === $action) {
            return $actor->role->isStaff();
        }

        if ($subject instanceof TicketContext && Action::VIEW === $action) {
            return $actor->role->isStaff()
                || $subject->ticket->holderId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof TicketContext && Action::UPDATE === $action) {
            return $actor->role->isStaff();
        }

        if ($subject instanceof TicketContext && Action::DELETE === $action) {
            return $actor->role->isStaff()
                || $subject->ticket->holderId()->__toString() === $actor->id->__toString();
        }

        return false;
    }
}
