<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\CancelEventCommand;
use App\Application\Event\CommandHandler\CancelEventUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/cancel', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/events/{eventId}/cancel',
    operationId: 'cancelEvent',
    summary: 'Cancel an event (DRAFT|PUBLISHED → CANCELLED)',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Event cancelled', content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event already cancelled or ended', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CancelEventController extends HttpController
{
    public function __construct(
        private readonly CancelEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new CancelEventCommand($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
