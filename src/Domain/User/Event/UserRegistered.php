<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Common\Event\DomainEventInterface;

final class UserRegistered implements DomainEventInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {}
}
