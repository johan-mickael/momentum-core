<?php

declare(strict_types=1);

namespace Tests\Functional\Event;

use Domain\User\Role;
use Infrastructure\Doctrine\Entity\VenueEntity;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\ApiTestCase;

final class EventApiTest extends ApiTestCase
{
    private string $venueId;
    private string $staffJwt;
    private string $buyerJwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venueId = $this->createVenue();

        $staff = $this->createUser('staff@event-test.com', Role::STAFF, 'Staff', 'User');
        $this->staffJwt = $this->jwtFor($staff);

        $buyer = $this->createUser('buyer@event-test.com', Role::BUYER, 'Buyer', 'User');
        $this->buyerJwt = $this->jwtFor($buyer);
    }

    // ─── POST /events ─────────────────────────────────────────────────────────

    public function testStaffCreatesEventReturns200WithEventData(): void
    {
        $response = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertArrayHasKey('id', $data);
        self::assertSame('Summer Festival', $data['name']);
        self::assertSame('DRAFT', $data['status']);
        self::assertSame('summer-festival-2040', $data['slug']);
        self::assertSame('FESTIVAL', $data['genre']);
    }

    public function testCreateEventWithMissingGenreReturns422(): void
    {
        $payload = $this->baseEventPayload();
        unset($payload['genre']);

        $response = $this->jsonRequest('POST', '/api/events', $payload, $this->staffJwt);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateEventWithInvalidGenreReturns422(): void
    {
        $payload = $this->baseEventPayload();
        $payload['genre'] = 'NOT_A_REAL_GENRE';

        $response = $this->jsonRequest('POST', '/api/events', $payload, $this->staffJwt);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateEventWithMissingNameReturns422(): void
    {
        $payload = $this->baseEventPayload();
        unset($payload['name']);

        $response = $this->jsonRequest('POST', '/api/events', $payload, $this->staffJwt);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateEventWithInvalidSlugReturns422(): void
    {
        $payload = $this->baseEventPayload();
        $payload['eventSlug'] = 'INVALID SLUG WITH SPACES';

        $response = $this->jsonRequest('POST', '/api/events', $payload, $this->staffJwt);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testCreateEventWithNonExistentVenueReturns404(): void
    {
        $payload = $this->baseEventPayload();
        $payload['venueId'] = '00000000-0000-4000-8000-000000000000';

        $response = $this->jsonRequest('POST', '/api/events', $payload, $this->staffJwt);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ─── GET /events ──────────────────────────────────────────────────────────

    public function testListEventsReturnsEmptyPaginatedResult(): void
    {
        $response = $this->jsonRequest('GET', '/api/events', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('pagination', $data);
        self::assertIsArray($data['data']);
    }

    public function testStaffSeesCreatedDraftEvent(): void
    {
        $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);

        $response = $this->jsonRequest('GET', '/api/events', jwt: $this->staffJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertGreaterThanOrEqual(1, $data['pagination']['total']);
        $statuses = array_column($data['data'], 'status');
        self::assertContains('DRAFT', $statuses);
    }

    public function testBuyerDoesNotSeeDraftEvents(): void
    {
        $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);

        $response = $this->jsonRequest('GET', '/api/events', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $statuses = array_column($data['data'], 'status');
        self::assertNotContains('DRAFT', $statuses);
    }

    public function testAnonymousRequestListsEventsWithoutAuthentication(): void
    {
        $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);

        $response = $this->jsonRequest('GET', '/api/events');
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $statuses = array_column($data['data'], 'status');
        self::assertNotContains('DRAFT', $statuses);
    }

    public function testAddTicketCategoryWithMismatchedCurrencyReturns409(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);

        $response = $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'VIP',
            'priceAmount' => 9000,
            'priceCurrency' => 'USD',
            'totalQuantity' => 10,
        ], $this->staffJwt);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    // ─── PATCH /events/{id}/publish ───────────────────────────────────────────

    public function testStaffPublishesEventWithTicketCategory(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);

        $response = $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame('PUBLISHED', $data['status']);
    }

    public function testPublishEventWithNoCategoriesReturns409(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);

        $response = $this->jsonRequest('PATCH', "/api/events/{$event['id']}/publish", jwt: $this->staffJwt);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    // ─── PATCH /events/{id}/cancel ────────────────────────────────────────────

    public function testStaffCancelsDraftEvent(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);

        $response = $this->jsonRequest('PATCH', "/api/events/{$event['id']}/cancel", jwt: $this->staffJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame('CANCELLED', $data['status']);
    }

    // ─── GET /events/{id} ────────────────────────────────────────────────────

    public function testGetPublishedEventIsVisibleToBuyer(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);
        $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);

        $response = $this->jsonRequest('GET', "/api/events/{$eventId}", jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame($eventId, $data['id']);
        self::assertSame('PUBLISHED', $data['status']);
    }

    public function testGetDraftEventReturns403ForBuyer(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);

        $response = $this->jsonRequest('GET', "/api/events/{$event['id']}", jwt: $this->buyerJwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testGetPublishedEventIsVisibleToAnonymousRequest(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);
        $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);

        $response = $this->jsonRequest('GET', "/api/events/{$eventId}");
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame($eventId, $data['id']);
        self::assertSame('PUBLISHED', $data['status']);
    }

    public function testGetDraftEventReturns403ForAnonymousRequest(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);

        $response = $this->jsonRequest('GET', "/api/events/{$event['id']}");

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    // ─── GET /events/{id}/ticket-categories ─────────────────────────────────

    public function testAnonymousRequestListsTicketCategoriesForPublishedEvent(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);
        $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);

        $response = $this->jsonRequest('GET', "/api/events/{$eventId}/ticket-categories");
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertCount(1, $data);
        self::assertSame('General', $data[0]['name']);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function baseEventPayload(): array
    {
        return [
            'name' => 'Summer Festival',
            'venueId' => $this->venueId,
            'eventSlug' => 'summer-festival-2040',
            'startAt' => '2040-07-01T19:00:00+02:00',
            'endAt' => '2040-07-01T22:00:00+02:00',
            'description' => 'A great festival',
            'genre' => 'FESTIVAL',
        ];
    }

    private function createVenue(): string
    {
        $venue = new VenueEntity();
        $venue->id = \uuid_create(UUID_TYPE_RANDOM);
        $venue->name = 'Grand Palais';
        $venue->street = '3 Av. du Général Eisenhower';
        $venue->city = 'Paris';
        $venue->zipCode = '75008';
        $venue->country = 'FR';
        $venue->latitude = 48.8660;
        $venue->longitude = 2.3125;
        $venue->capacity = 3000;

        $this->em->persist($venue);
        $this->em->flush();

        return $venue->id;
    }
}
