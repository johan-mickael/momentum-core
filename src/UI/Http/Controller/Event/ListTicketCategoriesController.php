<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Query\ListTicketCategoriesQuery;
use App\Application\Event\QueryHandler\ListTicketCategoriesUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories', methods: ['GET'])]
#[OA\Get(
    path: '/api/events/{eventId}/ticket-categories',
    operationId: 'listTicketCategories',
    summary: 'List ticket categories for an event',
    tags: ['Ticket Categories'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Ticket categories', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketCategoryResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListTicketCategoriesController extends HttpController
{
    public function __construct(
        private readonly ListTicketCategoriesUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new ListTicketCategoriesQuery($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
