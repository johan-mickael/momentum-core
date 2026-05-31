<?php

declare(strict_types=1);

namespace UI\Http\Controller\Ticket;

use Application\Ticket\Command\CheckInTicketCommand;
use Application\Ticket\CommandHandler\CheckInTicketUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Ticket\CheckInRequest;

#[Route('/tickets/check-in', methods: ['POST'])]
#[OA\Post(
    path: '/api/tickets/check-in',
    operationId: 'checkIn',
    summary: 'Check in a ticket by entry code (confirmed → used)',
    description: 'Alternative to POST /tickets/{ticketId}/use — looks up ticket by entryCode (QR scan flow).',
    tags: ['Tickets'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CheckInRequest')),
    responses: [
        new OA\Response(response: 204, description: 'Checked in'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Ticket with this entry code not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Ticket not in confirmed status', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CheckInController extends HttpController
{
    public function __construct(
        private readonly CheckInTicketUseCase $useCase,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload]
        CheckInRequest $request,
    ): JsonResponse {
        ($this->useCase)(
            new CheckInTicketCommand($request->entryCode),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
