<?php

declare(strict_types=1);

namespace Domain\Event\ValueObject;

use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\EventStatus;

enum EventVisibility
{
    case ALL;
    case PUBLISHED;

    public static function forActor(UserContext $actor): self
    {
        return $actor->role->isStaff() ? self::ALL : self::PUBLISHED;
    }

    public static function canActorView(UserContext $actor, EventStatus $status): bool
    {
        return $actor->role->isStaff() || EventStatus::PUBLISHED === $status;
    }
}
