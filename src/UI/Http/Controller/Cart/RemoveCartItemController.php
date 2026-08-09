<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Command\RemoveCartItemCommand;
use Application\Cart\CommandHandler\RemoveCartItemUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}/cart/items/{categoryId}', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/events/{eventId}/cart/items/{categoryId}',
    operationId: 'removeCartItem',
    summary: 'Remove a line from the cart and release its held tickets',
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Item removed, cart returned', content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Cart belongs to another buyer', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'No active cart, or no such item in it', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 410, description: 'Cart already expired', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class RemoveCartItemController extends HttpController
{
    public function __construct(
        private readonly RemoveCartItemUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId, string $categoryId): JsonResponse
    {
        $result = ($this->useCase)(
            new RemoveCartItemCommand(eventId: $eventId, categoryId: $categoryId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
