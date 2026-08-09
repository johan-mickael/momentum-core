<?php

declare(strict_types=1);

namespace Tests\Functional\Auth;

use Domain\User\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\ApiTestCase;

final class AuthApiTest extends ApiTestCase
{
    // ─── GET /auth/me ────────────────────────────────────────────────────────

    public function testMeReturns401WithoutToken(): void
    {
        $response = $this->jsonRequest('GET', '/api/auth/me');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testMeReturnsAuthenticatedUserProfile(): void
    {
        $user = $this->createUser('me@example.com', Role::BUYER, 'Alice', 'Doe');
        $jwt = $this->jwtFor($user);

        $response = $this->jsonRequest('GET', '/api/auth/me', jwt: $jwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame('me@example.com', $data['email']);
        self::assertSame('BUYER', $data['role']);
        self::assertSame('Alice', $data['firstName']);
        self::assertSame('Doe', $data['lastName']);
    }

    public function testMeReturnsStaffRole(): void
    {
        $user = $this->createUser('staff@example.com', Role::STAFF, 'Bob', 'Staff');
        $jwt = $this->jwtFor($user);

        $response = $this->jsonRequest('GET', '/api/auth/me', jwt: $jwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame('STAFF', $data['role']);
    }

    // ─── POST /auth/register ─────────────────────────────────────────────────

    public function testRegisterCreatesUserAndReturns201(): void
    {
        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'new@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $data = $this->assertJsonResponse($response, Response::HTTP_CREATED);

        self::assertArrayHasKey('id', $data);
        self::assertSame('new@example.com', $data['email']);
    }

    public function testRegisterWithDuplicateEmailReturns409(): void
    {
        $this->createUser('taken@example.com');

        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testRegisterWithMissingFieldsReturns422(): void
    {
        $response = $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'incomplete@example.com',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    // ─── POST /auth/login ────────────────────────────────────────────────────

    public function testLoginWithValidCredentialsSetsCookie(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'first_name' => 'Login',
            'last_name' => 'User',
        ]);

        $response = $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->getCookies());

        $cookie = $response->headers->getCookies()[0];
        self::assertSame('jwt_token', $cookie->getName());
        self::assertNotEmpty($cookie->getValue());
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->createUser('auth@example.com');

        $response = $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'wrong-password',
        ]);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
