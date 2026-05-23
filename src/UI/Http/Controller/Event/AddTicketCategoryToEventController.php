<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\AddTicketCategoryToEventCommand;
use App\Application\Event\CommandHandler\AddTicketCategoryToEventUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Event\AddTicketCategoryToEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories', methods: ['POST'])]
final class AddTicketCategoryToEventController extends HttpController
{
    public function __construct(
        private readonly AddTicketCategoryToEventUseCase $useCase,
    ) {}

    public function __invoke(
        string $eventId,
        #[MapRequestPayload] AddTicketCategoryToEventRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new AddTicketCategoryToEventCommand(
                eventId: $eventId,
                name: $request->name,
                priceAmount: $request->priceAmount,
                priceCurrency: $request->priceCurrency,
                totalQuantity: $request->totalQuantity,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, 201);
    }
}
