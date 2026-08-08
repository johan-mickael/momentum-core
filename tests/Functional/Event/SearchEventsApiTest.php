<?php

declare(strict_types=1);

namespace Tests\Functional\Event;

use Domain\User\Role;
use Infrastructure\Doctrine\Entity\VenueEntity;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\ApiTestCase;

final class SearchEventsApiTest extends ApiTestCase
{
    private string $venueId;
    private string $staffJwt;
    private string $buyerJwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venueId = $this->createVenue();

        $staff = $this->createUser('staff@search-test.com', Role::STAFF, 'Staff', 'User');
        $this->staffJwt = $this->jwtFor($staff);

        $buyer = $this->createUser('buyer@search-test.com', Role::BUYER, 'Buyer', 'User');
        $this->buyerJwt = $this->jwtFor($buyer);
    }

    public function testSearchByTextFindsMatchingEvent(): void
    {
        $eventId = $this->createAndPublishEvent('Zztargetronic Jazz Night', 'MUSIC');

        $response = $this->jsonRequest('GET', '/api/events/search?q=Zztargetronic', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $ids = array_column($data['data'], 'id');
        self::assertContains($eventId, $ids);
    }

    public function testSearchByGenreFiltersResults(): void
    {
        $this->createAndPublishEvent('Zztargetronic Sports Gala', 'SPORTS');
        $musicId = $this->createAndPublishEvent('Zztargetronic Music Gala', 'MUSIC');

        $response = $this->jsonRequest('GET', '/api/events/search?q=Zztargetronic&genre=MUSIC', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $genres = array_unique(array_column($data['data'], 'genre'));
        self::assertSame(['MUSIC'], $genres);

        $ids = array_column($data['data'], 'id');
        self::assertContains($musicId, $ids);
    }

    public function testSearchExposesPriceRangeAndGenre(): void
    {
        $eventId = $this->createAndPublishEvent('Zztargetronic Price Check', 'THEATRE', priceAmount: 4200);

        $response = $this->jsonRequest('GET', '/api/events/search?q=Zztargetronic Price Check', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $match = current(array_filter($data['data'], fn (array $e) => $e['id'] === $eventId));

        self::assertNotFalse($match);
        self::assertSame('THEATRE', $match['genre']);
        self::assertSame(4200, $match['minPrice']);
        self::assertSame(4200, $match['maxPrice']);
        self::assertSame('EUR', $match['currency']);
    }

    public function testBuyerNeverSeesDraftEventsInSearch(): void
    {
        $draftId = $this->createDraftEvent('Zztargetronic Secret Draft');

        $response = $this->jsonRequest('GET', '/api/events/search?q=Zztargetronic Secret Draft', jwt: $this->buyerJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $ids = array_column($data['data'], 'id');
        self::assertNotContains($draftId, $ids);
    }

    public function testStaffSeesDraftEventsInSearch(): void
    {
        $draftId = $this->createDraftEvent('Zztargetronic Visible Draft');

        $response = $this->jsonRequest('GET', '/api/events/search?q=Zztargetronic Visible Draft', jwt: $this->staffJwt);
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);

        $ids = array_column($data['data'], 'id');
        self::assertContains($draftId, $ids);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function createDraftEvent(string $name): string
    {
        $response = $this->jsonRequest('POST', '/api/events', [
            'name' => $name,
            'venueId' => $this->venueId,
            'eventSlug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'startAt' => '2040-07-01T19:00:00+02:00',
            'endAt' => '2040-07-01T22:00:00+02:00',
            'genre' => 'OTHER',
        ], $this->staffJwt);

        $event = json_decode($response->getContent(), true);

        return $event['id'];
    }

    private function createAndPublishEvent(string $name, string $genre, int $priceAmount = 2500): string
    {
        $response = $this->jsonRequest('POST', '/api/events', [
            'name' => $name,
            'venueId' => $this->venueId,
            'eventSlug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'startAt' => '2040-07-01T19:00:00+02:00',
            'endAt' => '2040-07-01T22:00:00+02:00',
            'genre' => $genre,
        ], $this->staffJwt);

        $event = json_decode($response->getContent(), true);
        $eventId = $event['id'];

        $this->jsonRequest('POST', "/api/events/{$eventId}/ticket-categories", [
            'name' => 'General',
            'priceAmount' => $priceAmount,
            'priceCurrency' => 'EUR',
            'totalQuantity' => 100,
        ], $this->staffJwt);

        $this->jsonRequest('PATCH', "/api/events/{$eventId}/publish", jwt: $this->staffJwt);

        return $eventId;
    }

    private function createVenue(): string
    {
        $venue = new VenueEntity();
        $venue->id = \uuid_create(UUID_TYPE_RANDOM);
        $venue->name = 'Search Test Venue';
        $venue->street = '1 Rue de la Recherche';
        $venue->city = 'Lyon';
        $venue->zipCode = '69000';
        $venue->country = 'FR';
        $venue->latitude = 45.7640;
        $venue->longitude = 4.8357;
        $venue->capacity = 1000;

        $this->em->persist($venue);
        $this->em->flush();

        return $venue->id;
    }
}
