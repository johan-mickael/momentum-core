<?php

declare(strict_types=1);

namespace App\Domain\Order\Security\Authorization;

use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\PolicyInterface;
use App\Domain\Common\Security\Authorization\Subject;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Order\Order;

final class OrderPolicy implements PolicyInterface
{
    public function supports(Subject|string $subject): bool
    {
        return Order::class === $subject || $subject instanceof OrderContext;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if ($subject === Order::class && $action === Action::CREATE) {
            return true;
        }

        if ($subject === Order::class && $action === Action::LIST) {
            return true;
        }

        if ($subject instanceof OrderContext && $action === Action::VIEW) {
            return $actor->role->isStaff()
                || $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof OrderContext && $action === Action::CHECKOUT) {
            return $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        if ($subject instanceof OrderContext && $action === Action::CANCEL) {
            return $actor->role->isStaff()
                || $subject->order->buyerId()->__toString() === $actor->id->__toString();
        }

        return false;
    }
}
