<?php

declare(strict_types=1);

namespace Domain\Event\Security\Authorization;

use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\Subject;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\Event;
use Domain\Event\ValueObject\EventVisibility;

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
            Event::class === $subject
            && Action::CREATE === $action
        ) {
            return $actor->role->isStaff();
        }

        if (
            Event::class === $subject
            && Action::LIST === $action
        ) {
            return true;
        }

        if (
            $subject instanceof EventContext
            && Action::VIEW === $action
        ) {
            return EventVisibility::canActorView($actor, $subject->event->status());
        }

        if (
            $subject instanceof EventContext
            && Action::UPDATE === $action
        ) {
            return $actor->role->isStaff();
        }

        return false;
    }
}
