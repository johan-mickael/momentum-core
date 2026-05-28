<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Application\Ticket\QueryHandler\ListTicketsByEventUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/tickets', methods: ['GET'])]
#[OA\Get(
    path: '/api/events/{eventId}/tickets',
    operationId: 'listTicketsByEvent',
    summary: 'List all tickets for an event (STAFF only)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Tickets for the event', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListTicketsByEventController extends HttpController
{
    public function __construct(
        private readonly ListTicketsByEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new ListTicketsByEventQuery($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
