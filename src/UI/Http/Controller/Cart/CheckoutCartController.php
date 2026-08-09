<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Command\CheckoutCartCommand;
use Application\Cart\CommandHandler\CheckoutCartUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}/cart/checkout', methods: ['POST'])]
#[OA\Post(
    path: '/api/events/{eventId}/cart/checkout',
    operationId: 'checkoutCart',
    summary: 'Convert the cart into an order (mints tickets, starts the payment TTL)',
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 201, description: 'Order placed (status: pending_payment)', content: new OA\JsonContent(ref: '#/components/schemas/PlaceOrderResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Cart belongs to another buyer', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'No active cart for this event', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Cart is empty', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 410, description: 'Cart already expired — its holds were released', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CheckoutCartController extends HttpController
{
    public function __construct(
        private readonly CheckoutCartUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new CheckoutCartCommand(eventId: $eventId),
            $this->getUserContext(),
        );

        return $this->json($result, Response::HTTP_CREATED);
    }
}
