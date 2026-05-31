<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

interface AuthorizationServiceInterface
{
    public function authorize(
        UserContext $actor,
        Action $action,
        Subject|string $subject,
    ): bool;
}
