<?php

declare(strict_types=1);

namespace UI\Http\Controller\Event;

use Application\Event\Command\FeatureEventCommand;
use Application\Event\CommandHandler\FeatureEventUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Event\FeatureEventRequest;

#[Route('/events/{eventId}/feature', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/events/{eventId}/feature',
    operationId: 'featureEvent',
    summary: 'Feature a published event on curated homepage rails, at a given rank',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FeatureEventRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Event featured', content: new OA\JsonContent(ref: '#/components/schemas/EventResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event is not published', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
#[IsGranted('MANAGE_EVENTS')]
final class FeatureEventController extends HttpController
{
    public function __construct(
        private readonly FeatureEventUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        #[MapRequestPayload]
        FeatureEventRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new FeatureEventCommand($eventId, $request->rank),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
