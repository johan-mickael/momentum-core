<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Query\ListEventsQuery;
use Application\Event\QueryHandler\ListEventsUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;
use UI\Http\Request\ListQueryParser;

#[Route('/events', methods: ['GET'])]
#[OA\Get(
    path: '/api/events',
    operationId: 'listEvents',
    summary: 'List events (STAFF sees all statuses, buyers see only PUBLISHED)',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100), description: 'Items per page'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['startAt', 'createdAt', 'name'], default: 'startAt'), description: 'Sort field'),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'ASC'), description: 'Sort direction'),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['DRAFT', 'PUBLISHED', 'CANCELLED', 'ENDED']), description: 'Filter by status (STAFF only)'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated list of events', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedEventsResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListEventsController extends HttpController
{
    private const array SORT_FIELDS = ['startAt', 'createdAt', 'name'];
    private const string DEFAULT_SORT = 'startAt';

    public function __construct(
        private readonly ListEventsUseCase $useCase,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, self::SORT_FIELDS, self::DEFAULT_SORT);

        $result = ($this->useCase)(
            new ListEventsQuery(
                page: $page,
                limit: $limit,
                sort: $sort,
                direction: $direction,
                statusFilter: $request->query->getString('status') ?: null,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
