<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Command\UpdateEventCommand;
use Application\Event\CommandHandler\UpdateEventUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Event\UpdateEventRequest;

#[Route('/events/{eventId}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/events/{eventId}',
    operationId: 'updateEvent',
    summary: 'Update an event',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateEventRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Event updated', content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or venue not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event is cancelled or ended', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
#[IsGranted('MANAGE_EVENTS')]
final class UpdateEventController extends HttpController
{
    public function __construct(
        private readonly UpdateEventUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        #[MapRequestPayload]
        UpdateEventRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            UpdateEventCommand::create(
                eventId: $eventId,
                name: $request->name,
                startAt: $request->startAt,
                endAt: $request->endAt,
                venueId: $request->venueId,
                description: $request->description,
                imageUrl: $request->imageUrl,
                genre: $request->genre,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
