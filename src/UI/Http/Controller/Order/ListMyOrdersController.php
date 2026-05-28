<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\QueryHandler\ListMyOrdersUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/orders/me',
    operationId: 'listMyOrders',
    summary: 'List authenticated user\'s orders',
    tags: ['Orders'],
    responses: [
        new OA\Response(response: 200, description: 'My orders', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListMyOrdersController extends HttpController
{
    public function __construct(
        private readonly ListMyOrdersUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListMyOrdersQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
