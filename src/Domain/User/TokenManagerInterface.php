<?php

declare(strict_types=1);

namespace Domain\User;

interface TokenManagerInterface
{
    public function generateFor(User $user): string;
}
