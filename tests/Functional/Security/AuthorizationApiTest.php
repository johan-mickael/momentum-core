<?php

declare(strict_types=1);

namespace Tests\Functional\Security;

use Domain\User\Role;
use Infrastructure\Doctrine\Entity\VenueEntity;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\ApiTestCase;

final class AuthorizationApiTest extends ApiTestCase
{
    private string $venueId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venueId = $this->createVenue();
    }

    // ─── Unauthenticated → 401 ───────────────────────────────────────────────

    public function testGetMeWithoutTokenReturns401(): void
    {
        $response = $this->jsonRequest('GET', '/api/auth/me');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateEventWithoutTokenReturns401(): void
    {
        $response = $this->jsonRequest('POST', '/api/events', $this->validEventPayload());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testPlaceOrderWithoutTokenReturns401(): void
    {
        $response = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => \uuid_create(UUID_TYPE_RANDOM),
            'items' => [['categoryId' => \uuid_create(UUID_TYPE_RANDOM), 'quantity' => 1]],
        ]);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetMyOrdersWithoutTokenReturns401(): void
    {
        $response = $this->jsonRequest('GET', '/api/orders/me');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ─── BUYER → 403 on staff-only routes ───────────────────────────────────

    public function testBuyerCannotCreateEvent(): void
    {
        $buyer = $this->createUser('buyer@example.com', Role::BUYER);
        $jwt = $this->jwtFor($buyer);

        $response = $this->jsonRequest('POST', '/api/events', $this->validEventPayload(), $jwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testBuyerCannotCreateVenue(): void
    {
        $buyer = $this->createUser('buyer2@example.com', Role::BUYER);
        $jwt = $this->jwtFor($buyer);

        $response = $this->jsonRequest('POST', '/api/venues', [
            'name' => 'Test Venue',
            'street' => '1 Rue de la Paix',
            'city' => 'Paris',
            'zipCode' => '75001',
            'country' => 'FR',
            'latitude' => 48.8698,
            'longitude' => 2.3304,
            'capacity' => 500,
        ], $jwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testBuyerCannotListTicketsByEvent(): void
    {
        $buyer = $this->createUser('buyer3@example.com', Role::BUYER);
        $jwt = $this->jwtFor($buyer);
        $eventId = \uuid_create(UUID_TYPE_RANDOM);

        $response = $this->jsonRequest('GET', "/api/events/{$eventId}/tickets", jwt: $jwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    // ─── STAFF → 200/201 on staff routes ────────────────────────────────────

    public function testStaffCanCreateEvent(): void
    {
        $staff = $this->createUser('staff@example.com', Role::STAFF, 'Staff', 'User');
        $jwt = $this->jwtFor($staff);

        $response = $this->jsonRequest('POST', '/api/events', $this->validEventPayload(), $jwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAdminCanCreateEvent(): void
    {
        $admin = $this->createUser('admin@example.com', Role::ADMIN, 'Admin', 'User');
        $jwt = $this->jwtFor($admin);

        $response = $this->jsonRequest('POST', '/api/events', $this->validEventPayload('admin-event'), $jwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // ─── BUYER → allowed on own resources ───────────────────────────────────

    public function testBuyerCanAccessOwnOrders(): void
    {
        $buyer = $this->createUser('buyer-orders@example.com', Role::BUYER);
        $jwt = $this->jwtFor($buyer);

        $response = $this->jsonRequest('GET', '/api/orders/me', jwt: $jwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testBuyerCanAccessOwnTickets(): void
    {
        $buyer = $this->createUser('buyer-tickets@example.com', Role::BUYER);
        $jwt = $this->jwtFor($buyer);

        $response = $this->jsonRequest('GET', '/api/tickets/me', jwt: $jwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function validEventPayload(string $slug = 'test-event-2040'): array
    {
        return [
            'name' => 'Test Event',
            'venueId' => $this->venueId,
            'eventSlug' => $slug,
            'startAt' => '2040-07-01T19:00:00+02:00',
            'endAt' => '2040-07-01T22:00:00+02:00',
            'genre' => 'OTHER',
        ];
    }

    private function createVenue(): string
    {
        $venue = new VenueEntity();
        $venue->id = \uuid_create(UUID_TYPE_RANDOM);
        $venue->name = 'Test Venue';
        $venue->street = '1 Rue de la Paix';
        $venue->city = 'Paris';
        $venue->zipCode = '75001';
        $venue->country = 'FR';
        $venue->latitude = 48.8698;
        $venue->longitude = 2.3304;
        $venue->capacity = 1000;

        $this->em->persist($venue);
        $this->em->flush();

        return $venue->id;
    }
}
