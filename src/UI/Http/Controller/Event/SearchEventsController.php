<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Query\SearchEventsQuery;
use Application\Event\QueryHandler\SearchEventsUseCase;
use Domain\Event\EventGenre;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UI\Http\Controller\HttpController;
use UI\Http\Request\ListQueryParser;

#[Route('/events/search', methods: ['GET'])]
#[OA\Get(
    path: '/api/events/search',
    operationId: 'searchEvents',
    summary: 'Advanced event search — full-text, genre/price/date/geo filters, relevance/date/price/popularity sort',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Full-text search query'),
        new OA\Parameter(name: 'genre', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Comma-separated list of genres'),
        new OA\Parameter(name: 'city', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Comma-separated list of venue cities'),
        new OA\Parameter(name: 'country', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Exact venue country match'),
        new OA\Parameter(name: 'priceMin', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Minimum ticket price, in cents'),
        new OA\Parameter(name: 'priceMax', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Maximum ticket price, in cents'),
        new OA\Parameter(name: 'dateFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
        new OA\Parameter(name: 'dateTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
        new OA\Parameter(name: 'lat', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
        new OA\Parameter(name: 'lon', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
        new OA\Parameter(name: 'radiusKm', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
        new OA\Parameter(name: 'featured', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Only staff-curated featured events (for hero rails)'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['relevance', 'date', 'price', 'popularity', 'featured'], default: 'date')),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'ASC')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated search results'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class SearchEventsController extends HttpController
{
    private const array SORT_FIELDS = ['relevance', 'date', 'price', 'popularity', 'featured'];
    private const string DEFAULT_SORT = 'date';

    public function __construct(
        private readonly SearchEventsUseCase $useCase,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query->get('q') ?: null;
        $defaultSort = null !== $query ? 'relevance' : self::DEFAULT_SORT;

        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, self::SORT_FIELDS, $defaultSort);

        $result = ($this->useCase)(
            new SearchEventsQuery(
                query: $query,
                genres: $this->parseGenres($request),
                cities: $this->parseCsv($request, 'city'),
                country: $request->query->get('country') ?: null,
                priceMin: $this->parseNullableInt($request, 'priceMin'),
                priceMax: $this->parseNullableInt($request, 'priceMax'),
                dateFrom: $request->query->get('dateFrom') ?: null,
                dateTo: $request->query->get('dateTo') ?: null,
                lat: $this->parseNullableFloat($request, 'lat'),
                lon: $this->parseNullableFloat($request, 'lon'),
                radiusKm: $this->parseNullableFloat($request, 'radiusKm'),
                sort: $sort,
                direction: $direction,
                featuredOnly: $request->query->getBoolean('featured'),
                page: $page,
                limit: $limit,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }

    /** @return string[] */
    private function parseGenres(Request $request): array
    {
        $raw = $request->query->get('genre');
        if (null === $raw || '' === $raw) {
            return [];
        }

        $values = array_map('trim', explode(',', $raw));

        return array_values(array_intersect($values, EventGenre::values()));
    }

    /** @return string[] */
    private function parseCsv(Request $request, string $key): array
    {
        $raw = $request->query->get($key);
        if (null === $raw || '' === $raw) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function parseNullableInt(Request $request, string $key): ?int
    {
        $value = $request->query->get($key);

        return null !== $value && '' !== $value ? (int) $value : null;
    }

    private function parseNullableFloat(Request $request, string $key): ?float
    {
        $value = $request->query->get($key);

        return null !== $value && '' !== $value ? (float) $value : null;
    }
}
