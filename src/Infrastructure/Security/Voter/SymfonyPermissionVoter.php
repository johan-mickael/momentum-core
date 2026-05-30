<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Common\Security\Authorization\Permission;
use App\Infrastructure\Security\User\SymfonySecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SymfonyPermissionVoter extends Voter
{
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return null !== Permission::tryFrom($attribute);
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof SymfonySecurityUser) {
            return false;
        }

        return $user
            ->getDomainUser()
            ->hasPermission(
                Permission::from($attribute)
            );
    }
}
