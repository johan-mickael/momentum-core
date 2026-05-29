<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Application\Ticket\QueryHandler\ListTicketsByEventUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\ListQueryParser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/tickets', methods: ['GET'])]
#[OA\Get(
    path: '/api/events/{eventId}/tickets',
    operationId: 'listTicketsByEvent',
    summary: 'List all tickets for an event (STAFF only)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1), description: 'Page number'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100), description: 'Items per page'),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['reservedAt', 'status'], default: 'reservedAt'), description: 'Sort field'),
        new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'DESC'), description: 'Sort direction'),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['RESERVED', 'CONFIRMED', 'CANCELLED', 'USED']), description: 'Filter by ticket status'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated list of tickets for the event', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedTicketsResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListTicketsByEventController extends HttpController
{
    private const array SORT_FIELDS   = ['reservedAt', 'status'];
    private const string DEFAULT_SORT = 'reservedAt';

    public function __construct(
        private readonly ListTicketsByEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId, Request $request): JsonResponse
    {
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, self::SORT_FIELDS, self::DEFAULT_SORT, 'DESC');

        $result = ($this->useCase)(
            new ListTicketsByEventQuery(
                eventId:      $eventId,
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
