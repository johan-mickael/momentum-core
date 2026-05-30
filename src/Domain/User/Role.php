<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Common\Security\Authorization\Permission;
use App\Domain\User\Exception\InvalidRole;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case STAFF = 'STAFF';
    case BUYER = 'BUYER';
    case USER = 'USER';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidRole::fromValue($value);
    }

    /**
     * (Not a big fan but here we go)
     * Adapter Symfony Security.
     * Example: ROLE_ADMIN.
     */
    public function securityRoles(): array
    {
        return array_map(
            static fn (Role $role) => 'ROLE_'.$role->value,
            $this->inheritedRoles(),
        );
    }

    public function inheritedRoles(): array
    {
        return match ($this) {
            self::ADMIN => [self::ADMIN, self::STAFF, self::BUYER, self::USER],

            self::STAFF => [self::STAFF, self::BUYER, self::USER],

            self::BUYER => [self::BUYER, self::USER],

            self::USER => [self::USER],
        };
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array(
            $permission,
            $this->permissions(),
            true,
        );
    }

    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => [Permission::MANAGE_USERS, Permission::MANAGE_EVENTS],

            self::STAFF => [Permission::MANAGE_EVENTS],

            self::BUYER, self::USER => [],
        };
    }

    public function isAdmin(): bool
    {
        return in_array(
            Role::ADMIN,
            $this->inheritedRoles(),
            true,
        );
    }

    public function isStaff(): bool
    {
        return in_array(
            self::STAFF,
            $this->inheritedRoles(),
            true,
        );
    }
}
