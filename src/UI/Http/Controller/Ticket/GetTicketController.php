<?php

declare(strict_types=1);

namespace UI\Http\Controller\Ticket;

use Application\Ticket\Query\GetTicketQuery;
use Application\Ticket\QueryHandler\GetTicketUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/tickets/{ticketId}', requirements: ['ticketId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
#[OA\Get(
    path: '/api/tickets/{ticketId}',
    operationId: 'getTicket',
    summary: 'Get a ticket by ID (holder sees own, STAFF sees any)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Ticket details', content: new OA\JsonContent(ref: '#/components/schemas/TicketResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Not the ticket holder', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Ticket not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class GetTicketController extends HttpController
{
    public function __construct(
        private readonly GetTicketUseCase $useCase,
    ) {
    }

    public function __invoke(string $ticketId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetTicketQuery($ticketId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
