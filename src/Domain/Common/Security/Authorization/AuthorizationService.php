<?php

declare(strict_types=1);

namespace Domain\Common\Security\Authorization;

final readonly class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * @param iterable<PolicyInterface> $policies
     */
    public function __construct(
        private iterable $policies,
    ) {
    }

    public function authorize(
        UserContext $actor,
        Action $action,
        Subject|string $subject,
    ): bool {
        foreach ($this->policies as $policy) {
            if ($policy->supports($subject)) {
                return $policy->authorize(
                    $actor,
                    $action,
                    $subject,
                );
            }
        }

        return false;
    }
}
