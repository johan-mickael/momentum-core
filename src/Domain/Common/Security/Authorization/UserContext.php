<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

use Domain\User\Role;
use Domain\User\ValueObject\UserId;

final readonly class UserContext
{
    public function __construct(
        public UserId $id,
        public Role $role,
    ) {
    }
}
