<?php

declare(strict_types=1);

namespace App\Domain\User;

interface TokenManagerInterface
{
    public function generateFor(User $user): string;
}
