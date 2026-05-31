<?php

declare(strict_types=1);

namespace Domain\User\Event;

use Domain\Common\Event\DomainEventInterface;

final class UserRegistered implements DomainEventInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {
    }
}
