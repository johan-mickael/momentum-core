<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\CancelTicketCommand;
use App\Application\Ticket\CommandHandler\CancelTicketUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/{ticketId}', requirements: ['ticketId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/tickets/{ticketId}',
    operationId: 'cancelTicket',
    summary: 'Cancel a ticket (holder or STAFF)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Ticket cancelled'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Not the ticket holder and not STAFF', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Ticket not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Ticket already used', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CancelTicketController extends HttpController
{
    public function __construct(
        private readonly CancelTicketUseCase $useCase,
    ) {
    }

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new CancelTicketCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
