<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\CommandHandler\PlaceOrderUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Order\PlaceOrderRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders', methods: ['POST'])]
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
