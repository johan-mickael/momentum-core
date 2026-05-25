<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

use App\Domain\Common\Security\Authorization\UserContext;

enum EventVisibility
{
    case ALL;
    case PUBLISHED;

    public static function forActor(UserContext $actor): self
    {
        return $actor->role->isStaff() ? self::ALL : self::PUBLISHED;
    }
}
