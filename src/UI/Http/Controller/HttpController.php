<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\Common\Security\Authorization\UserContext;
use App\Infrastructure\Security\User\SymfonySecurityUser;
use App\UI\Http\Exception\InvalidAuthenticatedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;

abstract class HttpController extends AbstractController
{
    protected function getUserContext(): UserContext
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedException('User is not authenticated.', 403);
        }

        if (!$user instanceof SymfonySecurityUser) {
            throw new InvalidAuthenticatedUser();
        }

        return $user->getUserContext();
    }
}
