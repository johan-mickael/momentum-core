<?php

declare(strict_types=1);

namespace Domain\Venue\Security\Authorization;

use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\Subject;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Venue\Venue;

final class VenuePolicy implements PolicyInterface
{
    public function supports(
        Subject|string $subject,
    ): bool {
        return Venue::class === $subject || $subject instanceof VenueContext;
    }

    public function authorize(
        UserContext $actor,
        Action $action,
        Subject|string $subject,
    ): bool {
        if (
            Venue::class === $subject
            && Action::CREATE === $action
        ) {
            return $actor->role->isStaff();
        }

        if (
            Venue::class === $subject
            && Action::LIST === $action
        ) {
            return $actor->role->isStaff();
        }

        if (
            $subject instanceof VenueContext
            && Action::VIEW === $action
        ) {
            return $actor->role->isStaff();
        }

        return false;
    }
}
