<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Command\CreateEventCommand;
use Application\Event\CommandHandler\CreateEventUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Event\CreateEventRequest;

#[Route('/events', methods: ['POST'])]
#[OA\Post(
    path: '/api/events',
    operationId: 'createEvent',
    summary: 'Create a new event',
    tags: ['Events'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateEventRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Event created', content: new OA\JsonContent(ref: '#/components/schemas/CreateEventResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Venue not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Slug already in use', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
#[IsGranted('MANAGE_EVENTS')]
final class CreateEventController extends HttpController
{
    public function __construct(
        private readonly CreateEventUseCase $createEventUseCase,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload]
        CreateEventRequest $createEventRequest,
    ): JsonResponse {
        $createEventCommand = CreateEventCommand::create(
            $createEventRequest->name,
            $createEventRequest->venueId,
            $createEventRequest->eventSlug,
            $createEventRequest->startAt,
            $createEventRequest->endAt,
            $createEventRequest->description,
            $createEventRequest->imageUrl,
            $createEventRequest->genre,
        );

        $createEventResult = ($this->createEventUseCase)($createEventCommand, $this->getUserContext());

        return $this->json($createEventResult);
    }
}
