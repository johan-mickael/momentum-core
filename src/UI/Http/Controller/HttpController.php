<?php

declare(strict_types=1);

namespace UI\Http\Controller;

use Domain\Common\Security\Authorization\UserContext;
use Infrastructure\Symfony\Security\SymfonySecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use UI\Http\Exception\InvalidAuthenticatedUser;

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
