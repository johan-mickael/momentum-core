<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

use Domain\User\Role;
use Domain\User\ValueObject\Email;
use Domain\User\ValueObject\UserId;

final readonly class UserContext
{
    private const string GUEST_ID = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        public UserId $id,
        public Role $role,
        public ?Email $email = null,
    ) {
    }

    public static function guest(): self
    {
        return new self(UserId::fromString(self::GUEST_ID), Role::USER);
    }
}
