<?php

declare(strict_types=1);

namespace UI\Http\Controller\Ticket;

use Application\Ticket\Command\ReserveTicketCommand;
use Application\Ticket\CommandHandler\ReserveTicketUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Ticket\ReserveTicketRequest;

#[Route('/events/{eventId}/tickets', methods: ['POST'])]
#[OA\Post(
    path: '/api/events/{eventId}/tickets',
    operationId: 'reserveTicket',
    summary: 'Reserve a ticket in a category',
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReserveTicketRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Ticket reserved', content: new OA\JsonContent(ref: '#/components/schemas/TicketResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or category not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'No tickets available', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ReserveTicketController extends HttpController
{
    public function __construct(
        private readonly ReserveTicketUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        #[MapRequestPayload]
        ReserveTicketRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new ReserveTicketCommand(
                eventId: $eventId,
                categoryId: $request->categoryId,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, 201);
    }
}
