<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Command\UnfeatureEventCommand;
use Application\Event\CommandHandler\UnfeatureEventUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}/unfeature', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/events/{eventId}/unfeature',
    operationId: 'unfeatureEvent',
    summary: 'Remove an event from curated homepage rails',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Event unfeatured', content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
#[IsGranted('MANAGE_EVENTS')]
final class UnfeatureEventController extends HttpController
{
    public function __construct(
        private readonly UnfeatureEventUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new UnfeatureEventCommand($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
