<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\UpdateTicketCategoryCommand;
use App\Application\Event\CommandHandler\UpdateTicketCategoryUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Event\UpdateTicketCategoryRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories/{categoryId}', methods: ['PUT'])]
final class UpdateTicketCategoryController extends HttpController
{
    public function __construct(
        private readonly UpdateTicketCategoryUseCase $useCase,
    ) {}

    public function __invoke(
        string $eventId,
        string $categoryId,
        #[MapRequestPayload] UpdateTicketCategoryRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new UpdateTicketCategoryCommand(
                eventId: $eventId,
                categoryId: $categoryId,
                name: $request->name,
                priceAmount: $request->priceAmount,
                priceCurrency: $request->priceCurrency,
                totalQuantity: $request->totalQuantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
