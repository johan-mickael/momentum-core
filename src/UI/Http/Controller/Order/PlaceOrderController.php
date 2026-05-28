<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\CommandHandler\PlaceOrderUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Order\PlaceOrderRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', methods: ['POST'])]
#[OA\Post(
    path: '/api/orders',
    operationId: 'placeOrder',
    summary: 'Place an order and reserve tickets',
    tags: ['Orders'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PlaceOrderRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Order placed (status: pending_payment)', content: new OA\JsonContent(ref: '#/components/schemas/PlaceOrderResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Event or category not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Event not published or no available slots', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class PlaceOrderController extends HttpController
{
    public function __construct(
        private readonly PlaceOrderUseCase $useCase,
    ) {}

    public function __invoke(
        #[MapRequestPayload] PlaceOrderRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new PlaceOrderCommand(
                eventId: $request->eventId,
                items:   $request->items,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, Response::HTTP_CREATED);
    }
}
