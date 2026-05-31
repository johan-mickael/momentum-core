<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Security\Jwt;

use Domain\User\TokenManagerInterface;
use Domain\User\User;
use Infrastructure\Symfony\Security\SymfonySecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class LexikJwtTokenGenerator implements TokenManagerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function generateFor(User $user): string
    {
        return $this->jwtManager->create(new SymfonySecurityUser($user));
    }
}
