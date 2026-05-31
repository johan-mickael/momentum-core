<?php

declare(strict_types=1);

namespace UI\Http\Controller\Ticket;

use Application\Ticket\Command\ConfirmTicketCommand;
use Application\Ticket\CommandHandler\ConfirmTicketUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/tickets/{ticketId}/confirm', methods: ['POST'])]
#[OA\Post(
    path: '/api/tickets/{ticketId}/confirm',
    operationId: 'confirmTicket',
    summary: 'Confirm a ticket (reserved → confirmed)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Ticket confirmed'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Ticket not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Ticket not in reserved status', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ConfirmTicketController extends HttpController
{
    public function __construct(
        private readonly ConfirmTicketUseCase $useCase,
    ) {
    }

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new ConfirmTicketCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
