<?php

declare(strict_types=1);

namespace UI\Http\Controller\Ticket;

use Application\Ticket\Command\MarkTicketAsUsedCommand;
use Application\Ticket\CommandHandler\MarkTicketAsUsedUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/tickets/{ticketId}/use', methods: ['POST'])]
#[OA\Post(
    path: '/api/tickets/{ticketId}/use',
    operationId: 'markTicketAsUsed',
    summary: 'Mark a ticket as used by ticketId (confirmed → used)',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'ticketId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Ticket marked as used'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Ticket not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Ticket not in confirmed status', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class MarkTicketAsUsedController extends HttpController
{
    public function __construct(
        private readonly MarkTicketAsUsedUseCase $useCase,
    ) {
    }

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new MarkTicketAsUsedCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
