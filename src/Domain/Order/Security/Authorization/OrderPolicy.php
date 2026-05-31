<?php

declare(strict_types=1);

namespace Domain\Order\Security\Authorization;

use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\Subject;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Order\Order;

final class OrderPolicy implements PolicyInterface
{
    public function supports(Subject|string $subject): bool
    {
        return Order::class === $subject || $subject instanceof OrderContext;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if (Order::class === $subject && Action::CREATE === $action) {
            return true;
        }

        if (Order::class === $subject && Action::LIST === $action) {
            return true;
        }

        if ($subject instanceof OrderContext && Action::VIEW === $action) {
            return $actor->role->isStaff()
                || $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof OrderContext && Action::CHECKOUT === $action) {
            return $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof OrderContext && Action::CANCEL === $action) {
            return $actor->role->isStaff()
                || $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        return false;
    }
}
