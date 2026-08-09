<?php

declare(strict_types=1);

namespace Tests\Functional\Order;

use Domain\User\Role;
use Infrastructure\Doctrine\Entity\VenueEntity;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\ApiTestCase;

final class OrderApiTest extends ApiTestCase
{
    private string $venueId;
    private string $staffJwt;
    private string $buyerJwt;
    private string $anotherBuyerJwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venueId = $this->createVenue();

        $staff = $this->createUser('staff@order-test.com', Role::STAFF, 'Staff', 'User');
        $this->staffJwt = $this->jwtFor($staff);

        $buyer = $this->createUser('buyer@order-test.com', Role::BUYER, 'Buyer', 'User');
        $this->buyerJwt = $this->jwtFor($buyer);

        $anotherBuyer = $this->createUser('other@order-test.com', Role::BUYER, 'Other', 'Buyer');
        $this->anotherBuyerJwt = $this->jwtFor($anotherBuyer);
    }

    // ─── POST /orders ─────────────────────────────────────────────────────────

    public function testBuyerPlacesOrderOnPublishedEventReturns201(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $response = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);

        $data = $this->assertJsonResponse($response, Response::HTTP_CREATED);

        self::assertArrayHasKey('id', $data);
        self::assertSame('pending_payment', $data['status']);
        self::assertCount(1, $data['items']);
    }

    public function testPlaceOrderOnNonPublishedEventReturns409(): void
    {
        $createResponse = $this->jsonRequest('POST', '/api/events', $this->baseEventPayload(), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $catResponse = $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 10,
        ], $this->staffJwt);
        $cat = json_decode($catResponse->getContent(), true);

        $response = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $cat['id'], 'quantity' => 1]],
        ], $this->buyerJwt);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testPlaceOrderWithInvalidEventIdReturns404(): void
    {
        $response = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => '00000000-0000-4000-8000-000000000000',
            'items' => [['categoryId' => '00000000-0000-4000-8000-000000000001', 'quantity' => 1]],
        ], $this->buyerJwt);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testPlaceOrderReducesAvailableInventory(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory(totalQuantity: 5);

        $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);

        $categoriesResponse = $this->jsonRequest('GET', "/api/events/{$eventId}/ticket-categories", jwt: $this->buyerJwt);
        $categories = json_decode($categoriesResponse->getContent(), true);
        $category = $categories[0];

        self::assertSame(4, $category['availableQuantity']);
    }

    // ─── GET /orders/me ───────────────────────────────────────────────────────

    public function testListMyOrdersReturnsOnlyBuyerOrders(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);

        $response = $this->jsonRequest('GET', '/api/orders/me', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        self::assertSame(1, $data['pagination']['total']);

        $otherResponse = $this->jsonRequest('GET', '/api/orders/me', jwt: $this->anotherBuyerJwt);
        $otherData = $this->assertJsonResponse($otherResponse, Response::HTTP_OK);

        self::assertSame(0, $otherData['pagination']['total']);
    }

    // ─── GET /orders/{id} ────────────────────────────────────────────────────

    public function testBuyerCanViewOwnOrder(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $placeResponse = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);
        $order = json_decode($placeResponse->getContent(), true);

        $response = $this->jsonRequest('GET', "/api/orders/{$order['id']}", jwt: $this->buyerJwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testBuyerCannotViewAnotherBuyerOrder(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $placeResponse = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);
        $order = json_decode($placeResponse->getContent(), true);

        $response = $this->jsonRequest('GET', "/api/orders/{$order['id']}", jwt: $this->anotherBuyerJwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testStaffCanViewAnyOrder(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $placeResponse = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);
        $order = json_decode($placeResponse->getContent(), true);

        $response = $this->jsonRequest('GET', "/api/orders/{$order['id']}", jwt: $this->staffJwt);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // ─── DELETE /orders/{id} ─────────────────────────────────────────────────

    public function testBuyerCancelsPendingOrder(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $placeResponse = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);
        $order = json_decode($placeResponse->getContent(), true);

        $response = $this->jsonRequest('DELETE', "/api/orders/{$order['id']}", jwt: $this->buyerJwt);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testBuyerCannotCancelAnotherBuyerOrder(): void
    {
        [$eventId, $categoryId] = $this->createPublishedEventWithCategory();

        $placeResponse = $this->jsonRequest('POST', '/api/orders', [
            'eventId' => $eventId,
            'items' => [['categoryId' => $categoryId, 'quantity' => 1]],
        ], $this->buyerJwt);
        $order = json_decode($placeResponse->getContent(), true);

        $response = $this->jsonRequest('DELETE', "/api/orders/{$order['id']}", jwt: $this->anotherBuyerJwt);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /** @return array{string, string} [eventId, categoryId] */
    private function createPublishedEventWithCategory(int $totalQuantity = 50): array
    {
        static $counter = 0;
        ++$counter;

        $createResponse = $this->jsonRequest('POST', '/api/events', array_merge($this->baseEventPayload(), [
            'eventSlug' => "event-{$counter}-order-test",
        ]), $this->staffJwt);
        $event = json_decode($createResponse->getContent(), true);
        $eventId = $event['id'];

        $catResponse = $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General Admission',
            'priceAmount' => 2500,
            'priceCurrency' => 'EUR',
            'totalQuantity' => $totalQuantity,
        ], $this->staffJwt);
        $cat = json_decode($catResponse->getContent(), true);

        $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);

        return [$eventId, $cat['id']];
    }

    private function baseEventPayload(): array
    {
        return [
            'name' => 'Order Test Festival',
            'venueId' => $this->venueId,
            'eventSlug' => 'order-test-festival',
            'startAt' => '2040-07-01T19:00:00+02:00',
            'endAt' => '2040-07-01T22:00:00+02:00',
            'genre' => 'FESTIVAL',
        ];
    }

    private function createVenue(): string
    {
        $venue = new VenueEntity();
        $venue->id = \uuid_create(UUID_TYPE_RANDOM);
        $venue->name = 'Order Test Venue';
        $venue->street = '5 Rue du Test';
        $venue->city = 'Lyon';
        $venue->zipCode = '69001';
        $venue->country = 'FR';
        $venue->latitude = 45.7640;
        $venue->longitude = 4.8357;
        $venue->capacity = 500;

        $this->em->persist($venue);
        $this->em->flush();

        return $venue->id;
    }
}
