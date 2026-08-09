<?php

declare(strict_types=1);

namespace Domain\Cart\Security\Authorization;

use Domain\Cart\Cart;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\Subject;
use Domain\Common\Security\Authorization\UserContext;

final class CartPolicy implements PolicyInterface
{
    public function supports(Subject|string $subject): bool
    {
        return Cart::class === $subject || $subject instanceof CartContext;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if (Cart::class === $subject && (Action::CREATE === $action || Action::LIST === $action)) {
            return true;
        }

        if ($subject instanceof CartContext) {
            return $subject->cart->buyerId()->__toString() === $actor->id->__toString();
        }

        return false;
    }
}
