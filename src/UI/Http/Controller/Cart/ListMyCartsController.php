<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Query\ListMyCartsQuery;
use Application\Cart\QueryHandler\ListMyCartsUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/carts/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/carts/me',
    operationId: 'listMyCarts',
    summary: "List every active cart for the authenticated buyer, across all events they're shopping for",
    tags: ['Cart'],
    responses: [
        new OA\Response(response: 200, description: 'Active carts', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/MyCartSummary'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListMyCartsController extends HttpController
{
    public function __construct(
        private readonly ListMyCartsUseCase $useCase,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(new ListMyCartsQuery(), $this->getUserContext());

        return $this->json($result);
    }
}
