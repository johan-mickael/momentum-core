<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Query\GetEventByIdQuery;
use Application\Event\QueryHandler\GetEventByIdUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}', requirements: ['eventId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
#[OA\Get(
    path: '/api/events/{eventId}',
    operationId: 'getEvent',
    summary: 'Get an event by ID (public — non-PUBLISHED events are only visible to STAFF)',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Event details', content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')),
        new OA\Response(response: 403, description: 'Event is not PUBLISHED and requester is not STAFF', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class GetEventController extends HttpController
{
    public function __construct(
        private readonly GetEventByIdUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetEventByIdQuery($eventId),
            $this->getUserContextOrGuest(),
        );

        return $this->json($result);
    }
}
