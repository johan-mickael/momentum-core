<?php

declare(strict_types=1);

namespace UI\Http\Controller;

use Domain\Common\Security\Authorization\UserContext;
use Infrastructure\Symfony\Security\SymfonySecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use UI\Http\Exception\InvalidAuthenticatedUser;
use UI\Http\Exception\UnauthenticatedRequest;

abstract class HttpController extends AbstractController
{
    protected function getUserContext(): UserContext
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw new UnauthenticatedRequest();
        }

        if (!$user instanceof SymfonySecurityUser) {
            throw new InvalidAuthenticatedUser();
        }

        return $user->getUserContext();
    }
}
