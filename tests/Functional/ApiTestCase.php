<?php

declare(strict_types=1);

namespace Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Domain\User\Role;
use Infrastructure\Doctrine\Entity\UserEntity;
use Infrastructure\Symfony\Security\SymfonyUserProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(
        string $email,
        Role $role = Role::BUYER,
        string $firstName = 'Test',
        string $lastName = 'User',
    ): UserEntity {
        $user = new UserEntity();
        $user->id = \uuid_create(UUID_TYPE_RANDOM);
        $user->email = $email;
        $user->role = $role;
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->createdAt = new \DateTimeImmutable();
        $user->passwordHash = null;

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function jwtFor(UserEntity $user): string
    {
        $provider = static::getContainer()->get(SymfonyUserProvider::class);
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $securityUser = $provider->loadUserByIdentifier($user->email);

        return $jwtManager->create($securityUser);
    }

    protected function jsonRequest(
        string $method,
        string $uri,
        mixed $body = null,
        ?string $jwt = null,
    ): Response {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if (null !== $jwt) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$jwt;
        }

        $this->client->request(
            method: $method,
            uri: $uri,
            server: $headers,
            content: null !== $body ? json_encode($body, JSON_THROW_ON_ERROR) : null,
        );

        return $this->client->getResponse();
    }

    protected function assertJsonResponse(Response $response, int $expectedStatus): array
    {
        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertJson($response->getContent());

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
