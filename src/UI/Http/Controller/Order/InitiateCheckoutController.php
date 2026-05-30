<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\InitiateCheckoutCommand;
use App\Application\Order\CommandHandler\InitiateCheckoutUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}/checkout', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
#[OA\Post(
    path: '/api/orders/{orderId}/checkout',
    operationId: 'initiateCheckout',
    summary: 'Create a Stripe Checkout session for an order',
    tags: ['Orders'],
    parameters: [
        new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Stripe checkout URL', content: new OA\JsonContent(ref: '#/components/schemas/CheckoutResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Only the order buyer can initiate checkout', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Order not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Order not in pending_payment status', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class InitiateCheckoutController extends HttpController
{
    public function __construct(
        private readonly InitiateCheckoutUseCase $useCase,
    ) {
    }

    public function __invoke(string $orderId): JsonResponse
    {
        $result = ($this->useCase)(
            new InitiateCheckoutCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
