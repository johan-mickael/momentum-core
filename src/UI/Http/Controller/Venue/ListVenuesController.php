<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Venue;

use App\Application\Venue\Query\ListVenuesQuery;
use App\Application\Venue\QueryHandler\ListVenuesUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\ListQueryParser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues', methods: ['GET'])]
#[OA\Get(
    path: '/api/venues',
    operationId: 'listVenues',
    summary: 'List all venues',
    tags: ['Venues'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100), description: 'Items per page'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['name', 'city', 'country', 'capacity'], default: 'name'), description: 'Sort field'),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'ASC'), description: 'Sort direction'),
        new OA\Parameter(name: 'city', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by city (partial match)'),
        new OA\Parameter(name: 'country', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by country (partial match)'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated list of venues', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedVenuesResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListVenuesController extends HttpController
{
    private const array SORT_FIELDS   = ['name', 'city', 'country', 'capacity'];
    private const string DEFAULT_SORT = 'name';

    public function __construct(
        private readonly ListVenuesUseCase $listVenuesUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, self::SORT_FIELDS, self::DEFAULT_SORT);

        $result = ($this->listVenuesUseCase)(
            new ListVenuesQuery(
                page:          $page,
                limit:         $limit,
                sort:          $sort,
                direction:     $direction,
                cityFilter:    $request->query->getString('city') ?: null,
                countryFilter: $request->query->getString('country') ?: null,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
