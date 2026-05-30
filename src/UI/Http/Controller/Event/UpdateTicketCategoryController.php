<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\UpdateTicketCategoryCommand;
use App\Application\Event\CommandHandler\UpdateTicketCategoryUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Event\UpdateTicketCategoryRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories/{categoryId}', methods: ['PUT'])]
#[OA\Put(
    path: '/api/events/{eventId}/ticket-categories/{categoryId}',
    operationId: 'updateTicketCategory',
    summary: 'Update a ticket category',
    tags: ['Ticket Categories'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateTicketCategoryRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Category updated', content: new OA\JsonContent(ref: '#/components/schemas/TicketCategoryResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or category not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Duplicate name', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'totalQuantity below reservedQuantity or validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class UpdateTicketCategoryController extends HttpController
{
    public function __construct(
        private readonly UpdateTicketCategoryUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        string $categoryId,
        #[MapRequestPayload]
        UpdateTicketCategoryRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new UpdateTicketCategoryCommand(
                eventId: $eventId,
                categoryId: $categoryId,
                name: $request->name,
                priceAmount: $request->priceAmount,
                priceCurrency: $request->priceCurrency,
                totalQuantity: $request->totalQuantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
