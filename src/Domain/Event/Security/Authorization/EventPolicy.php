<?php

declare(strict_types=1);

namespace App\Domain\Event\Security\Authorization;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\PolicyInterface;
use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\Event;

final class EventPolicy implements PolicyInterface
{
    public function supports(
        Subject|string $subject,
    ): bool {
        return Event::class === $subject || $subject instanceof EventContext;
    }

    public function authorize(
        UserContext $actor,
        Action $action,
        Subject|string $subject,
    ): bool {
        if (
            $subject === Event::class
            && $action === Action::CREATE
        ) {
            return $actor->role->isStaff();
        }

        if (
            $subject === Event::class
            && $action === Action::LIST
        ) {
            return true;
        }

        if (
            $subject instanceof EventContext
            && $action === Action::VIEW
        ) {
            return true;
        }

        if (
            $subject instanceof EventContext
            && $action === Action::UPDATE
        ) {
            return $actor->role->isStaff();
        }

        return false;
    }
}
