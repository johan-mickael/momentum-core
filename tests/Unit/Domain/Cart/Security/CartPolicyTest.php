<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cart\Security;

use Domain\Cart\Cart;
use Domain\Cart\Security\Authorization\CartContext;
use Domain\Cart\Security\Authorization\CartPolicy;
use Domain\Cart\ValueObject\CartId;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Event\ValueObject\EventId;
use Domain\User\Role;
use Domain\User\ValueObject\UserId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CartPolicyTest extends TestCase
{
    private const string OWNER_ID = '019e0000-0000-7000-8000-000000000001';
    private const string OTHER_USER_ID = '019e0000-0000-7000-8000-000000000002';
    private const string EVENT_ID = '019e0000-0000-7000-8000-000000000003';
    private const string CART_ID = '019e0000-0000-7000-8000-000000000004';

    private CartPolicy $policy;
    private Cart $cart;

    protected function setUp(): void
    {
        $this->policy = new CartPolicy();
        $this->cart = Cart::open(
            CartId::fromString(self::CART_ID),
            UserId::fromString(self::OWNER_ID),
            EventId::fromString(self::EVENT_ID),
            new \DateTimeImmutable('2040-01-01 10:00:00'),
        );
    }

    public function testSupportsReturnsTrueForCartClass(): void
    {
        self::assertTrue($this->policy->supports(Cart::class));
    }

    public function testSupportsReturnsTrueForCartContext(): void
    {
        self::assertTrue($this->policy->supports(new CartContext($this->cart)));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->policy->supports('Domain\Order\Order'));
    }

    public function testCreateAllowsAnyAuthenticatedRole(): void
    {
        foreach ([Role::ADMIN, Role::STAFF, Role::BUYER, Role::USER] as $role) {
            self::assertTrue(
                $this->policy->authorize($this->makeUserContext(self::OWNER_ID, $role), Action::CREATE, Cart::class),
            );
        }
    }

    public function testListAllowsAnyAuthenticatedRole(): void
    {
        foreach ([Role::ADMIN, Role::STAFF, Role::BUYER, Role::USER] as $role) {
            self::assertTrue(
                $this->policy->authorize($this->makeUserContext(self::OWNER_ID, $role), Action::LIST, Cart::class),
            );
        }
    }

    public function testActionOtherThanCreateOrListOnCartClassIsDenied(): void
    {
        $actor = $this->makeUserContext(self::OWNER_ID, Role::BUYER);

        self::assertFalse($this->policy->authorize($actor, Action::VIEW, Cart::class));
    }

    #[DataProvider('ownerScopedActions')]
    public function testOwnerIsAllowedOnTheirOwnCart(Action $action): void
    {
        $actor = $this->makeUserContext(self::OWNER_ID, Role::BUYER);

        self::assertTrue($this->policy->authorize($actor, $action, new CartContext($this->cart)));
    }

    #[DataProvider('ownerScopedActions')]
    public function testAnotherBuyerIsDeniedOnSomeoneElsesCart(Action $action): void
    {
        $actor = $this->makeUserContext(self::OTHER_USER_ID, Role::BUYER);

        self::assertFalse($this->policy->authorize($actor, $action, new CartContext($this->cart)));
    }

    #[DataProvider('ownerScopedActions')]
    public function testStaffHasNoOverrideOnAnotherBuyersCart(Action $action): void
    {
        // Unlike orders, carts are purely private to the buyer while
        // shopping — staff get no support-desk override here.
        $actor = $this->makeUserContext(self::OTHER_USER_ID, Role::ADMIN);

        self::assertFalse($this->policy->authorize($actor, $action, new CartContext($this->cart)));
    }

    /** @return array<string, array{Action}> */
    public static function ownerScopedActions(): array
    {
        return [
            'view' => [Action::VIEW],
            'update' => [Action::UPDATE],
            'delete' => [Action::DELETE],
            'checkout' => [Action::CHECKOUT],
        ];
    }

    private function makeUserContext(string $id, Role $role): UserContext
    {
        return new UserContext(UserId::fromString($id), $role);
    }
}
