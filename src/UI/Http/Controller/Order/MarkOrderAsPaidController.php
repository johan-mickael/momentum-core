<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Order;

use App\Application\Order\Command\MarkOrderAsPaidCommand;
use App\Application\Order\CommandHandler\MarkOrderAsPaidUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Order\MarkOrderAsPaidRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders/{orderId}/pay', methods: ['POST'])]
final class MarkOrderAsPaidController extends HttpController
{
    public function __construct(
        private readonly MarkOrderAsPaidUseCase $useCase,
    ) {}

    public function __invoke(
        string $orderId,
        #[MapRequestPayload] MarkOrderAsPaidRequest $request,
    ): JsonResponse {
        ($this->useCase)(
            new MarkOrderAsPaidCommand(
                orderId: $orderId,
                paidAt:  $request->paidAt,
            ),
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
