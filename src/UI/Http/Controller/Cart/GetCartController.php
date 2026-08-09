<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Query\GetCartQuery;
use Application\Cart\QueryHandler\GetCartUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}/cart', methods: ['GET'])]
#[OA\Get(
    path: '/api/events/{eventId}/cart',
    operationId: 'getCart',
    summary: "Get the current buyer's active cart for this event",
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Cart state (status "empty" if none exists yet)', content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class GetCartController extends HttpController
{
    public function __construct(
        private readonly GetCartUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetCartQuery(eventId: $eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
