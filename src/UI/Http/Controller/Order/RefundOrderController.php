<?php

declare(strict_types=1);

namespace UI\Http\Controller\Order;

use Application\Order\Command\RefundOrderCommand;
use Application\Order\CommandHandler\RefundOrderUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/orders/{orderId}/refund', methods: ['POST'])]
#[OA\Post(
    path: '/api/orders/{orderId}/refund',
    operationId: 'refundOrder',
    summary: 'Refund a paid order via Stripe (STAFF only)',
    tags: ['Orders'],
    parameters: [
        new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Order refunded'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Order not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Order has no payment reference', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class RefundOrderController extends HttpController
{
    public function __construct(
        private readonly RefundOrderUseCase $useCase,
    ) {
    }

    public function __invoke(string $orderId): JsonResponse
    {
        ($this->useCase)(
            new RefundOrderCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
