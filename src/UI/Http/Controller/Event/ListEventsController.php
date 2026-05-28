<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\QueryHandler\ListEventsUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', methods: ['GET'])]
#[OA\Get(
    path: '/api/events',
    operationId: 'listEvents',
    summary: 'List events (STAFF sees all statuses, buyers see only PUBLISHED)',
    tags: ['Events'],
    responses: [
        new OA\Response(response: 200, description: 'List of events', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/EventResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListEventsController extends HttpController
{
    public function __construct(
        private readonly ListEventsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListEventsQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
