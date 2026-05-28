<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\RemoveTicketCategoryCommand;
use App\Application\Event\CommandHandler\RemoveTicketCategoryUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories/{categoryId}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/events/{eventId}/ticket-categories/{categoryId}',
    operationId: 'removeTicketCategory',
    summary: 'Remove a ticket category (requires zero reservations)',
    tags: ['Ticket Categories'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Category removed'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or category not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Category has active reservations', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class RemoveTicketCategoryController extends HttpController
{
    public function __construct(
        private readonly RemoveTicketCategoryUseCase $useCase,
    ) {}

    public function __invoke(string $eventId, string $categoryId): JsonResponse
    {
        ($this->useCase)(
            new RemoveTicketCategoryCommand($eventId, $categoryId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
