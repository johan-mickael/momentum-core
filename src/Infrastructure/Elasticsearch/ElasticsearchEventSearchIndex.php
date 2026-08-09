<?php

declare(strict_types=1);

namespace Infrastructure\Elasticsearch;

use Application\Event\Result\EventSearchResult;
use Application\Event\Result\VenueSummary;
use Application\Event\Search\EventSearchCriteria;
use Application\Event\Search\EventSearchDocument;
use Application\Event\Search\EventSearchFacet;
use Application\Event\Search\EventSearchIndexInterface;
use Application\Event\Search\EventSearchPage;
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

    public function search(EventSearchCriteria $criteria): EventSearchPage
    {
        $response = $this->client->search([
            'index' => self::INDEX,
            'body' => [
                'query' => $this->buildQuery($criteria),
                // genre/city/country are applied here instead of in `query` so they
                // narrow the returned hits WITHOUT narrowing what the top-level
                // `aggs` below see — a `filter` aggregation can only ever narrow the
                // set the top-level query already matched, never widen it back, so
                // any facet filter placed in `query` would be inescapable for aggs.
                'post_filter' => ['bool' => ['filter' => $this->buildFacetDimensionFilters($criteria)]],
                'sort' => $this->buildSort($criteria),
                'from' => $criteria->pagination->offset(),
                'size' => $criteria->pagination->limit,
                'track_total_hits' => true,
                'aggs' => $this->buildAggs($criteria),
            ],
        ])->asArray();

        $items = array_map(
            fn (array $hit) => $this->toResult($hit['_source']),
            $response['hits']['hits'],
        );

        return new EventSearchPage(
            items: $items,
            total: $response['hits']['total']['value'],
            page: $criteria->pagination->page,
            limit: $criteria->pagination->limit,
            facets: $this->toFacets($response['aggregations'] ?? []),
        );
    }

    /**
     * Each facet is aggregated over the base filters plus every OTHER
     * facet dimension's selection — never its own ("disjunctive"
     * faceting) — so picking a genre still shows every other genre's
     * count instead of collapsing the chip bar down to only the one
     * that's already selected.
     *
     * @return array<string, mixed>
     */
    private function buildAggs(EventSearchCriteria $criteria): array
    {
        $terms = [
            'genre' => ['field' => 'genre', 'size' => 20],
            'city' => ['field' => 'venue.city', 'size' => 50],
            'country' => ['field' => 'venue.country', 'size' => 50],
        ];

        $aggs = [];
        foreach ($terms as $field => $termsAgg) {
            $filters = [
                ...$this->buildBaseFilters($criteria),
                ...$this->buildFacetDimensionFilters($criteria, exclude: $field),
            ];
            $aggs[$field] = [
                'filter' => ['bool' => ['filter' => $filters]],
                'aggs' => ['values' => ['terms' => $termsAgg]],
            ];
        }

        return $aggs;
    }

    /**
     * @param array<string, array{values: array{buckets: array<int, array{key: string, doc_count: int}>}}> $aggregations
     *
     * @return array<string, EventSearchFacet[]>
     */
    private function toFacets(array $aggregations): array
    {
        $facets = [];
        foreach ($aggregations as $field => $aggregation) {
            $facets[$field] = array_map(
                fn (array $bucket) => new EventSearchFacet(value: $bucket['key'], count: $bucket['doc_count']),
                $aggregation['values']['buckets'],
            );
        }

        return $facets;
    }

    /** @return array<string, mixed> */
    private function buildQuery(EventSearchCriteria $criteria): array
    {
        return [
            'bool' => [
                'must' => [$this->buildTextMatch($criteria)],
                'filter' => $this->buildBaseFilters($criteria),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function buildTextMatch(EventSearchCriteria $criteria): array
    {
        if (null !== $criteria->query && '' !== $criteria->query) {
            return [
                'multi_match' => [
                    'query' => $criteria->query,
                    'fields' => ['name^3', 'description'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        return ['match_all' => new \stdClass()];
    }

    /**
     * Filters that apply everywhere alike: the main query, `post_filter`'s
     * result set, and every facet aggregation. None of these are
     * facetable dimensions, so there's no self-exclusion concern.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildBaseFilters(EventSearchCriteria $criteria): array
    {
        $filter = [];

        if ($criteria->publishedOnly) {
            $filter[] = ['term' => ['status' => 'PUBLISHED']];
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

        return $filter;
    }

    /**
     * Facetable dimension filters (genre/city/country) — kept out of the
     * top-level query and applied via `post_filter` instead, so they
     * narrow hits without narrowing what every facet aggregation sees.
     * Pass $exclude to drop one dimension when building a facet's own
     * cross-filter (see buildAggs()).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFacetDimensionFilters(EventSearchCriteria $criteria, ?string $exclude = null): array
    {
        $filter = [];

        if ('genre' !== $exclude && [] !== $criteria->genres) {
            $filter[] = ['terms' => ['genre' => $criteria->genres]];
        }

        if ('city' !== $exclude && null !== $criteria->city && '' !== $criteria->city) {
            $filter[] = ['term' => ['venue.city' => $criteria->city]];
        }

        if ('country' !== $exclude && null !== $criteria->country && '' !== $criteria->country) {
            $filter[] = ['term' => ['venue.country' => $criteria->country]];
        }

        return $filter;
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
            heroImageUrl: $source['heroImageUrl'] ?? null,
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
            'heroImageUrl' => $document->heroImageUrl,
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
