<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\AddTicketCategoryToEventCommand;
use App\Application\Event\CommandHandler\AddTicketCategoryToEventUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Event\AddTicketCategoryToEventRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories', methods: ['POST'])]
#[OA\Post(
    path: '/api/events/{eventId}/ticket-categories',
    operationId: 'addTicketCategory',
    summary: 'Add a ticket category to an event',
    tags: ['Ticket Categories'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AddTicketCategoryToEventRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Category added', content: new OA\JsonContent(ref: '#/components/schemas/TicketCategoryResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event is cancelled or name is duplicate', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class AddTicketCategoryToEventController extends HttpController
{
    public function __construct(
        private readonly AddTicketCategoryToEventUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        #[MapRequestPayload]
        AddTicketCategoryToEventRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new AddTicketCategoryToEventCommand(
                eventId: $eventId,
                name: $request->name,
                priceAmount: $request->priceAmount,
                priceCurrency: $request->priceCurrency,
                totalQuantity: $request->totalQuantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, 201);
    }
}
