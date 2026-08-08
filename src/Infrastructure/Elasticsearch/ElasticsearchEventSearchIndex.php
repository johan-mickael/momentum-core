<?php

declare(strict_types=1);

namespace Infrastructure\Elasticsearch;

use Application\Event\Result\EventSearchResult;
use Application\Event\Result\VenueSummary;
use Application\Event\Search\EventSearchCriteria;
use Application\Event\Search\EventSearchDocument;
use Application\Event\Search\EventSearchIndexInterface;
use Domain\Common\ValueObject\PaginatedList;
use Domain\Event\ValueObject\EventId;
use Elastic\Elasticsearch\Client;

final readonly class ElasticsearchEventSearchIndex implements EventSearchIndexInterface
{
    public const string INDEX = 'events';

    public function __construct(
        private Client $client,
    ) {
    }

    public function index(EventSearchDocument $document): void
    {
        $fields = $this->toFields($document);

        $this->client->update([
            'index' => self::INDEX,
            'id' => $document->id,
            'refresh' => 'wait_for',
            'body' => [
                'doc' => $fields,
                'upsert' => array_merge($fields, ['popularity' => 0]),
            ],
        ]);
    }

    public function incrementPopularity(EventId $id, int $delta): void
    {
        $this->client->update([
            'index' => self::INDEX,
            'id' => (string) $id,
            'refresh' => 'wait_for',
            'body' => [
                'script' => [
                    'lang' => 'painless',
                    'source' => 'ctx._source.popularity = Math.max(0, (ctx._source.containsKey("popularity") ? ctx._source.popularity : 0) + params.delta)',
                    'params' => ['delta' => $delta],
                ],
                'upsert' => ['popularity' => max(0, $delta)],
            ],
        ]);
    }

    public function search(EventSearchCriteria $criteria): PaginatedList
    {
        $response = $this->client->search([
            'index' => self::INDEX,
            'body' => [
                'query' => $this->buildQuery($criteria),
                'sort' => $this->buildSort($criteria),
                'from' => $criteria->pagination->offset(),
                'size' => $criteria->pagination->limit,
                'track_total_hits' => true,
            ],
        ])->asArray();

        $items = array_map(
            fn (array $hit) => $this->toResult($hit['_source']),
            $response['hits']['hits'],
        );

        return new PaginatedList(
            items: $items,
            total: $response['hits']['total']['value'],
            page: $criteria->pagination->page,
            limit: $criteria->pagination->limit,
        );
    }

    /** @return array<string, mixed> */
    private function buildQuery(EventSearchCriteria $criteria): array
    {
        $must = [];
        $filter = [];

        if (null !== $criteria->query && '' !== $criteria->query) {
            $must[] = [
                'multi_match' => [
                    'query' => $criteria->query,
                    'fields' => ['name^3', 'description'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        } else {
            $must[] = ['match_all' => new \stdClass()];
        }

        if ($criteria->publishedOnly) {
            $filter[] = ['term' => ['status' => 'PUBLISHED']];
        }

        if ([] !== $criteria->genres) {
            $filter[] = ['terms' => ['genre' => $criteria->genres]];
        }

        if ($criteria->featuredOnly) {
            $filter[] = ['term' => ['featured' => true]];
        }

        if (null !== $criteria->priceMin || null !== $criteria->priceMax) {
            $filter[] = ['range' => ['maxPrice' => ['gte' => $criteria->priceMin ?? 0]]];
            if (null !== $criteria->priceMax) {
                $filter[] = ['range' => ['minPrice' => ['lte' => $criteria->priceMax]]];
            }
        }

        if (null !== $criteria->dateFrom || null !== $criteria->dateTo) {
            $range = [];
            if (null !== $criteria->dateFrom) {
                $range['gte'] = $criteria->dateFrom;
            }
            if (null !== $criteria->dateTo) {
                $range['lte'] = $criteria->dateTo;
            }
            $filter[] = ['range' => ['startAt' => $range]];
        }

        if ($criteria->hasGeoFilter()) {
            $filter[] = [
                'geo_distance' => [
                    'distance' => "{$criteria->radiusKm}km",
                    'venue.location' => ['lat' => $criteria->lat, 'lon' => $criteria->lon],
                ],
            ];
        }

        return ['bool' => ['must' => $must, 'filter' => $filter]];
    }

    /** @return array<int, array<string, mixed>|string> */
    private function buildSort(EventSearchCriteria $criteria): array
    {
        $sort = $criteria->sort;
        if ('relevance' === $sort && (null === $criteria->query || '' === $criteria->query)) {
            $sort = 'date';
        }

        $direction = 'DESC' === $criteria->direction ? 'desc' : 'asc';

        return match ($sort) {
            'relevance' => ['_score'],
            'price' => [['minPrice' => $direction]],
            'popularity' => [['popularity' => $direction]],
            'featured' => [['featuredRank' => ['order' => 'asc', 'missing' => '_last']]],
            default => [['startAt' => $direction]],
        };
    }

    /** @param array<string, mixed> $source */
    private function toResult(array $source): EventSearchResult
    {
        $venue = $source['venue'] ?? [];
        $location = $venue['location'] ?? null;

        return new EventSearchResult(
            id: $source['id'],
            name: $source['name'],
            venue: new VenueSummary(
                id: $source['venueId'],
                name: $source['venueName'],
                street: $venue['street'] ?? '',
                city: $venue['city'] ?? '',
                zipCode: $venue['zipCode'] ?? '',
                country: $venue['country'] ?? '',
                latitude: $location['lat'] ?? null,
                longitude: $location['lon'] ?? null,
            ),
            slug: $source['slug'],
            genre: $source['genre'],
            startAt: $source['startAt'],
            endAt: $source['endAt'],
            status: $source['status'],
            description: $source['description'] ?? null,
            imageUrl: $source['imageUrl'] ?? null,
            minPrice: $source['minPrice'] ?? null,
            maxPrice: $source['maxPrice'] ?? null,
            currency: $source['currency'] ?? null,
            popularity: $source['popularity'] ?? 0,
            featured: $source['featured'] ?? false,
            featuredRank: $source['featuredRank'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    private function toFields(EventSearchDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'description' => $document->description,
            'genre' => $document->genre,
            'status' => $document->status,
            'startAt' => $document->startAt,
            'endAt' => $document->endAt,
            'slug' => $document->slug,
            'imageUrl' => $document->imageUrl,
            'venueId' => $document->venueId,
            'venueName' => $document->venueName,
            'venue' => [
                'street' => $document->venueStreet,
                'city' => $document->venueCity,
                'zipCode' => $document->venueZipCode,
                'country' => $document->venueCountry,
                'location' => null !== $document->venueLatitude && null !== $document->venueLongitude
                    ? ['lat' => $document->venueLatitude, 'lon' => $document->venueLongitude]
                    : null,
            ],
            'minPrice' => $document->minPrice,
            'maxPrice' => $document->maxPrice,
            'currency' => $document->currency,
            'featured' => $document->featured,
            'featuredRank' => $document->featuredRank,
        ];
    }
}
