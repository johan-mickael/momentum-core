<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

enum Permission: string
{
    case MANAGE_USERS = 'MANAGE_USERS';
    case MANAGE_EVENTS = 'MANAGE_EVENTS';
}
