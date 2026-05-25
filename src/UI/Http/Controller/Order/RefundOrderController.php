<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\RefundOrderCommand;
use App\Application\Order\CommandHandler\RefundOrderUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}/refund', methods: ['POST'])]
final class RefundOrderController extends HttpController
{
    public function __construct(
        private readonly RefundOrderUseCase $useCase,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        ($this->useCase)(
            new RefundOrderCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
