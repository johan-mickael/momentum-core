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
        return Order::class === $subject;
    }

    public function authorize(UserContext $actor, Action $action, Subject|string $subject): bool
    {
        if ($subject === Order::class && $action === Action::CREATE) {
            return true; // any authenticated user may place an order
        }

        return false;
    }
}
