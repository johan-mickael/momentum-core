<?php

declare(strict_types=1);

namespace App\Domain\Ticket\Security\Authorization;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\PolicyInterface;
use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Ticket\Ticket;

final class TicketPolicy implements PolicyInterface
{
    public function supports(Subject|string $subject): bool
    {
        return Ticket::class === $subject || $subject instanceof TicketContext;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if ($subject === Ticket::class && $action === Action::CREATE) {
            return true;
        }

        if ($subject === Ticket::class && $action === Action::VIEW) {
            return true;
        }

        if ($subject === Ticket::class && $action === Action::LIST) {
            return $actor->role->isStaff();
        }

        if ($subject instanceof TicketContext && $action === Action::VIEW) {
            return $actor->role->isStaff()
                || $subject->ticket->holderId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof TicketContext && $action === Action::UPDATE) {
            return $actor->role->isStaff();
        }

        if ($subject instanceof TicketContext && $action === Action::DELETE) {
            return $actor->role->isStaff()
                || $subject->ticket->holderId()->__toString() === $actor->id->__toString();
        }

        return false;
    }
}
