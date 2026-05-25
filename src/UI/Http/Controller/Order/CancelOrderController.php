<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\CancelOrderCommand;
use App\Application\Order\CommandHandler\CancelOrderUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['DELETE'])]
final class CancelOrderController extends HttpController
{
    public function __construct(
        private readonly CancelOrderUseCase $useCase,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        ($this->useCase)(
            new CancelOrderCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
