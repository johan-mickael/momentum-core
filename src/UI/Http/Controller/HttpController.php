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

    /**
     * For endpoints public visitors may hit without a JWT — falls back to a
     * non-staff guest actor instead of rejecting the request. Existing
     * authorization policies already treat non-staff actors as read-only, so
     * this doesn't grant guests anything a BUYER/USER wouldn't also have.
     */
    protected function getUserContextOrGuest(): UserContext
    {
        $user = $this->getUser();

        if (is_null($user)) {
            return UserContext::guest();
        }

        if (!$user instanceof SymfonySecurityUser) {
            throw new InvalidAuthenticatedUser();
        }

        return $user->getUserContext();
    }
}
