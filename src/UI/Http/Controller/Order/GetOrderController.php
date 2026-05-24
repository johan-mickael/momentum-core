<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Query\GetOrderQuery;
use App\Application\Order\QueryHandler\GetOrderUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
final class GetOrderController extends HttpController
{
    public function __construct(
        private readonly GetOrderUseCase $useCase,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetOrderQuery($orderId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
