<?php

declare(strict_types=1);

namespace App\Domain\Venue\Security\Authorization;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\PolicyInterface;
use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Venue\Venue;

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
            $subject === Venue::class
            && $action === Action::CREATE
        ) {
            return $actor->role->isStaff();
        }

        if (
            $subject === Venue::class
            && $action === Action::LIST
        ) {
            return $actor->role->isStaff();
        }

        if (
            $subject instanceof VenueContext
            && $action === Action::VIEW
        ) {
            return $actor->role->isStaff();
        }

        return false;
    }
}
