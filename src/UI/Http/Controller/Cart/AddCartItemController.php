<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Command\AddCartItemCommand;
use Application\Cart\CommandHandler\AddCartItemUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Cart\AddCartItemRequest;

#[Route('/events/{eventId}/cart/items', methods: ['POST'])]
#[OA\Post(
    path: '/api/events/{eventId}/cart/items',
    operationId: 'addCartItem',
    summary: 'Add tickets to the cart (holds inventory, opens or extends the cart TTL)',
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AddCartItemRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Item added, cart returned', content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or category not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event not published, sold out, or cart line/quantity limit reached', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class AddCartItemController extends HttpController
{
    public function __construct(
        private readonly AddCartItemUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        #[MapRequestPayload]
        AddCartItemRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new AddCartItemCommand(
                eventId: $eventId,
                categoryId: $request->categoryId,
                quantity: $request->quantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, Response::HTTP_CREATED);
    }
}
