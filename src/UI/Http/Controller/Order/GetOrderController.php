<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Query\GetOrderQuery;
use App\Application\Order\QueryHandler\GetOrderUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
#[OA\Get(
    path: '/api/orders/{orderId}',
    operationId: 'getOrder',
    summary: 'Get an order by ID (buyer sees own, STAFF sees any)',
    tags: ['Orders'],
    parameters: [
        new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Order details', content: new OA\JsonContent(ref: '#/components/schemas/OrderResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Not the order buyer', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Order not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class GetOrderController extends HttpController
{
    public function __construct(
        private readonly GetOrderUseCase $useCase,
    ) {
    }

    public function __invoke(string $orderId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetOrderQuery($orderId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
