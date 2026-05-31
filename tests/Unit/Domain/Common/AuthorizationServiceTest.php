<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationService;
use Domain\Common\Security\Authorization\PolicyInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\User\Role;
use Domain\User\ValueObject\UserId;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    private UserContext $actor;

    protected function setUp(): void
    {
        $this->actor = new UserContext(
            id: UserId::fromString(self::VALID_UUID),
            role: Role::STAFF,
        );
    }

    public function testAuthorizeReturnsTrueWhenPolicySupportsAndAuthorizes(): void
    {
        $policy = $this->makePolicy(supports: true, authorizes: true);
        $service = new AuthorizationService([$policy]);

        $result = $service->authorize($this->actor, Action::CREATE, 'SomeClass');

        self::assertTrue($result);
    }

    public function testAuthorizeReturnsFalseWhenPolicySupportsButDenies(): void
    {
        $policy = $this->makePolicy(supports: true, authorizes: false);
        $service = new AuthorizationService([$policy]);

        $result = $service->authorize($this->actor, Action::CREATE, 'SomeClass');

        self::assertFalse($result);
    }

    public function testAuthorizeReturnsFalseWhenNoPolicySupportsTheSubject(): void
    {
        $policy = $this->makePolicy(supports: false, authorizes: true);
        $service = new AuthorizationService([$policy]);

        $result = $service->authorize($this->actor, Action::CREATE, 'SomeClass');

        self::assertFalse($result);
    }

    public function testAuthorizeReturnsFalseWithNoPolicies(): void
    {
        $service = new AuthorizationService([]);

        $result = $service->authorize($this->actor, Action::CREATE, 'SomeClass');

        self::assertFalse($result);
    }

    public function testAuthorizeUsesFirstSupportingPolicy(): void
    {
        $denying = $this->makePolicy(supports: true, authorizes: false);
        $allowing = $this->makePolicy(supports: true, authorizes: true);

        $service = new AuthorizationService([$denying, $allowing]);

        // The first matching policy wins; the second should never be reached.
        $result = $service->authorize($this->actor, Action::CREATE, 'SomeClass');

        self::assertFalse($result);
    }

    private function makePolicy(bool $supports, bool $authorizes): PolicyInterface&Stub
    {
        $policy = $this->createStub(PolicyInterface::class);

        $policy->method('supports')->willReturn($supports);

        if ($supports) {
            $policy->method('authorize')->willReturn($authorizes);
        }

        return $policy;
    }
}
