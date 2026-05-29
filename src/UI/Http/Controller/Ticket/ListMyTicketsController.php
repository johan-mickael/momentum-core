<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\QueryHandler\ListMyTicketsUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\ListQueryParser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/tickets/me',
    operationId: 'listMyTickets',
    summary: 'List authenticated user\'s own tickets',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100), description: 'Items per page'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['reservedAt', 'status'], default: 'reservedAt'), description: 'Sort field'),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'DESC'), description: 'Sort direction'),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['RESERVED', 'CONFIRMED', 'CANCELLED', 'USED']), description: 'Filter by ticket status'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated list of my tickets', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedTicketsResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListMyTicketsController extends HttpController
{
    private const array SORT_FIELDS   = ['reservedAt', 'status'];
    private const string DEFAULT_SORT = 'reservedAt';

    public function __construct(
        private readonly ListMyTicketsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, self::SORT_FIELDS, self::DEFAULT_SORT, 'DESC');

        $result = ($this->useCase)(
            new ListMyTicketsQuery(
                page:         $page,
                limit:        $limit,
                sort:         $sort,
                direction:    $direction,
                statusFilter: $request->query->getString('status') ?: null,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
