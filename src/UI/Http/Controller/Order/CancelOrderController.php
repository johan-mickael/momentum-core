<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\CancelOrderCommand;
use App\Application\Order\CommandHandler\CancelOrderUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/orders/{orderId}',
    operationId: 'cancelOrder',
    summary: 'Cancel a pending order (buyer or STAFF)',
    tags: ['Orders'],
    parameters: [
        new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Order cancelled'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Not the order buyer and not STAFF', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Order not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CancelOrderController extends HttpController
{
    public function __construct(
        private readonly CancelOrderUseCase $useCase,
    ) {
    }

    public function __invoke(string $orderId): JsonResponse
    {
        ($this->useCase)(
            new CancelOrderCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
