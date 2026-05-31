<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

interface PolicyInterface
{
    public function supports(
        Subject|string $subject,
    ): bool;

    public function authorize(
        UserContext $actor,
        Action $action,
        Subject|string $subject,
    ): bool;
}
