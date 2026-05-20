<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\Common\Security\Authorization\Permission;
use App\Domain\User\Exception\InvalidRole;
use App\Domain\User\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testFromStringWithValidRole(): void
    {
        self::assertSame(Role::ADMIN, Role::fromString('ADMIN'));
        self::assertSame(Role::STAFF, Role::fromString('STAFF'));
        self::assertSame(Role::BUYER, Role::fromString('BUYER'));
        self::assertSame(Role::USER, Role::fromString('USER'));
    }

    public function testFromStringWithInvalidRoleThrows(): void
    {
        $this->expectException(InvalidRole::class);

        Role::fromString('SUPERUSER');
    }

    public function testAdminInheritsAllRoles(): void
    {
        $inherited = Role::ADMIN->inheritedRoles();

        self::assertContains(Role::ADMIN, $inherited);
        self::assertContains(Role::STAFF, $inherited);
        self::assertContains(Role::BUYER, $inherited);
        self::assertContains(Role::USER, $inherited);
    }

    public function testStaffInheritsStaffBuyerAndUser(): void
    {
        $inherited = Role::STAFF->inheritedRoles();

        self::assertNotContains(Role::ADMIN, $inherited);
        self::assertContains(Role::STAFF, $inherited);
        self::assertContains(Role::BUYER, $inherited);
        self::assertContains(Role::USER, $inherited);
    }

    public function testBuyerInheritsBuyerAndUser(): void
    {
        $inherited = Role::BUYER->inheritedRoles();

        self::assertNotContains(Role::ADMIN, $inherited);
        self::assertNotContains(Role::STAFF, $inherited);
        self::assertContains(Role::BUYER, $inherited);
        self::assertContains(Role::USER, $inherited);
    }

    public function testUserInheritsOnlyUser(): void
    {
        $inherited = Role::USER->inheritedRoles();

        self::assertSame([Role::USER], $inherited);
    }

    public function testAdminSecurityRolesContainAllRoles(): void
    {
        $securityRoles = Role::ADMIN->securityRoles();

        self::assertContains('ROLE_ADMIN', $securityRoles);
        self::assertContains('ROLE_STAFF', $securityRoles);
        self::assertContains('ROLE_BUYER', $securityRoles);
        self::assertContains('ROLE_USER', $securityRoles);
    }

    public function testUserSecurityRolesContainsOnlyRoleUser(): void
    {
        $securityRoles = Role::USER->securityRoles();

        self::assertSame(['ROLE_USER'], $securityRoles);
    }

    public function testAdminHasManageUsersPermission(): void
    {
        self::assertTrue(Role::ADMIN->hasPermission(Permission::MANAGE_USERS));
    }

    public function testAdminHasManageEventsPermission(): void
    {
        self::assertTrue(Role::ADMIN->hasPermission(Permission::MANAGE_EVENTS));
    }

    public function testStaffHasManageEventsPermission(): void
    {
        self::assertTrue(Role::STAFF->hasPermission(Permission::MANAGE_EVENTS));
    }

    public function testStaffDoesNotHaveManageUsersPermission(): void
    {
        self::assertFalse(Role::STAFF->hasPermission(Permission::MANAGE_USERS));
    }

    public function testBuyerHasNoPermissions(): void
    {
        self::assertFalse(Role::BUYER->hasPermission(Permission::MANAGE_USERS));
        self::assertFalse(Role::BUYER->hasPermission(Permission::MANAGE_EVENTS));
    }

    public function testUserHasNoPermissions(): void
    {
        self::assertFalse(Role::USER->hasPermission(Permission::MANAGE_USERS));
        self::assertFalse(Role::USER->hasPermission(Permission::MANAGE_EVENTS));
    }

    public function testIsAdminReturnsTrueOnlyForAdmin(): void
    {
        self::assertTrue(Role::ADMIN->isAdmin());
        self::assertFalse(Role::STAFF->isAdmin());
        self::assertFalse(Role::BUYER->isAdmin());
        self::assertFalse(Role::USER->isAdmin());
    }

    public function testIsStaffReturnsTrueForAdminAndStaff(): void
    {
        self::assertTrue(Role::ADMIN->isStaff());
        self::assertTrue(Role::STAFF->isStaff());
        self::assertFalse(Role::BUYER->isStaff());
        self::assertFalse(Role::USER->isStaff());
    }
}
