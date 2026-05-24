<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\InitiateCheckoutCommand;
use App\Application\Order\CommandHandler\InitiateCheckoutUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}/checkout', requirements: ['orderId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['POST'])]
final class InitiateCheckoutController extends HttpController
{
    public function __construct(
        private readonly InitiateCheckoutUseCase $useCase,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $result = ($this->useCase)(
            new InitiateCheckoutCommand($orderId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
