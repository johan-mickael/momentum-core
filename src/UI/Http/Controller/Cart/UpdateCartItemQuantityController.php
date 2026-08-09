<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Command\UpdateCartItemQuantityCommand;
use Application\Cart\CommandHandler\UpdateCartItemQuantityUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;
use UI\Http\Request\Cart\UpdateCartItemQuantityRequest;

#[Route('/events/{eventId}/cart/items/{categoryId}', methods: ['PATCH'])]
#[OA\Patch(
    path: '/api/events/{eventId}/cart/items/{categoryId}',
    operationId: 'updateCartItemQuantity',
    summary: 'Set the absolute quantity for a cart line (0 removes it), resets the cart TTL',
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCartItemQuantityRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Quantity updated, cart returned', content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Cart belongs to another buyer', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'No active cart, or no such item/category in it', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Sold out, or quantity limit reached', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 410, description: 'Cart already expired', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class UpdateCartItemQuantityController extends HttpController
{
    public function __construct(
        private readonly UpdateCartItemQuantityUseCase $useCase,
    ) {
    }

    public function __invoke(
        string $eventId,
        string $categoryId,
        #[MapRequestPayload]
        UpdateCartItemQuantityRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new UpdateCartItemQuantityCommand(
                eventId: $eventId,
                categoryId: $categoryId,
                quantity: $request->quantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
