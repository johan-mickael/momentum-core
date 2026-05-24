<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\QueryHandler\ListMyOrdersUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/me', methods: ['GET'])]
final class ListMyOrdersController extends HttpController
{
    public function __construct(
        private readonly ListMyOrdersUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListMyOrdersQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
